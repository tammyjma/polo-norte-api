<?php

namespace App\Adapters;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;
use Throwable;

class MeteoAdapter
{
    public function obtenerPronostico(string $zona, string $fecha): string
    {
        $url = env('METEO_SOAP_URL');
        $timeout = (int) env('METEO_TIMEOUT', 3);

        $zonaEscapada = htmlspecialchars($zona, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $fechaEscapada = htmlspecialchars($fecha, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        $xmlRequest = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                  xmlns:mt="http://polonorte.beatlech/meteo">
  <soapenv:Body>
    <mt:ObtenerPronosticoRequest>
      <mt:zona>{$zonaEscapada}</mt:zona>
      <mt:fecha>{$fechaEscapada}</mt:fecha>
    </mt:ObtenerPronosticoRequest>
  </soapenv:Body>
</soapenv:Envelope>
XML;

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'text/xml; charset=utf-8'])
                ->withBody($xmlRequest, 'text/xml')
                ->post($url);

            if ($response->failed()) {
                throw new ApiException(
                    'METEO_NO_DISPONIBLE',
                    'El servicio meteorológico respondió con error HTTP',
                    503
                );
            }

            $body = $response->body();

            if (str_contains($body, 'Fault') || str_contains($body, 'Estación sin señal')) {
                throw new ApiException(
                    'METEO_SIN_SENAL',
                    'Estación sin señal en servicio meteorológico',
                    503
                );
            }

            $xml = @simplexml_load_string($body);
            if ($xml === false) {
                throw new ApiException(
                    'METEO_XML_INVALIDO',
                    'Respuesta XML inválida del servicio meteorológico',
                    503
                );
            }

            $xml->registerXPathNamespace('mt', 'http://polonorte.beatlech/meteo');
            $nodos = $xml->xpath('//mt:pronostico');

            if (!$nodos || !isset($nodos[0])) {
                $nodosSinPrefijo = $xml->xpath('//*[local-name()="pronostico"]');
                if (!$nodosSinPrefijo || !isset($nodosSinPrefijo[0])) {
                    throw new ApiException(
                        'METEO_SIN_PRONOSTICO',
                        'No se encontró el campo pronostico en la respuesta SOAP',
                        503
                    );
                }
                return trim((string) $nodosSinPrefijo[0]);
            }

            return trim((string) $nodos[0]);
        } catch (ApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ApiException(
                'METEO_TIMEOUT',
                'El servicio meteorológico no respondió dentro de los 3 segundos o ocurrió un error de conexión',
                503
            );
        }
    }
}
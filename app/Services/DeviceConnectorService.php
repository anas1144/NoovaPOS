<?php

namespace App\Services;

use App\Models\DeviceConnector;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Executes a no-code device connector: substitutes {{placeholders}} into the
 * configured request, calls the device/service over HTTP, and reads the result
 * using the configured dot-path mappings. No per-device code required.
 */
class DeviceConnectorService
{
    /**
     * Run the verify call for a connector with the given variables.
     *
     * @return array{ok:bool, matched:bool, employee:?string, raw:mixed, error:?string}
     */
    public function verify(DeviceConnector $connector, array $vars): array
    {
        return $this->call($connector, $connector->verify_path, $vars);
    }

    public function enroll(DeviceConnector $connector, array $vars): array
    {
        return $this->call($connector, $connector->enroll_path, $vars);
    }

    private function call(DeviceConnector $connector, ?string $path, array $vars): array
    {
        if ($connector->run_on === DeviceConnector::RUN_CLIENT) {
            return ['ok' => false, 'matched' => false, 'employee' => null, 'raw' => null,
                'error' => 'This connector runs in the browser (local device); call it from the kiosk.'];
        }

        $url = rtrim((string) $connector->base_url, '/') . '/' . ltrim((string) $path, '/');
        $body = $this->substitute($connector->request_template ?? [], $vars);

        try {
            $request = Http::timeout($connector->timeout ?: 15)
                ->withHeaders($this->headers($connector));

            $method = strtolower($connector->http_method ?: 'post');
            $response = in_array($method, ['get', 'delete'], true)
                ? $request->{$method}($url, $body)
                : $request->{$method}($url, $body);

            $json = $response->json() ?? [];
        } catch (\Throwable $e) {
            return ['ok' => false, 'matched' => false, 'employee' => null, 'raw' => null, 'error' => $e->getMessage()];
        }

        $matched = $this->isSuccess($connector, $json);
        $employee = $connector->response_employee_path
            ? Arr::get($json, $connector->response_employee_path)
            : null;

        return [
            'ok'       => true,
            'matched'  => $matched,
            'employee' => $employee !== null ? (string) $employee : null,
            'raw'      => $json,
            'error'    => null,
        ];
    }

    private function headers(DeviceConnector $connector): array
    {
        $headers = is_array($connector->headers) ? $connector->headers : [];

        switch ($connector->auth_type) {
            case 'api_key':
                if ($connector->auth_header) {
                    $headers[$connector->auth_header] = $connector->auth_token;
                }
                break;
            case 'bearer':
                $headers['Authorization'] = 'Bearer ' . $connector->auth_token;
                break;
            case 'basic':
                $headers['Authorization'] = 'Basic ' . base64_encode((string) $connector->auth_token);
                break;
        }

        return $headers;
    }

    private function isSuccess(DeviceConnector $connector, array $json): bool
    {
        if (! $connector->response_success_path) {
            // No mapping given → treat a present employee value as success.
            return $connector->response_employee_path
                ? Arr::get($json, $connector->response_employee_path) !== null
                : true;
        }
        $value = Arr::get($json, $connector->response_success_path);
        $expected = $connector->success_value;
        if ($expected === null || $expected === '') {
            return (bool) $value;
        }
        return (string) $value === (string) $expected;
    }

    /** Recursively replace {{key}} placeholders in strings using $vars. */
    private function substitute($template, array $vars)
    {
        if (is_array($template)) {
            $out = [];
            foreach ($template as $k => $v) {
                $out[$k] = $this->substitute($v, $vars);
            }
            return $out;
        }
        if (is_string($template)) {
            return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', function ($m) use ($vars) {
                return Arr::get($vars, $m[1], '');
            }, $template);
        }
        return $template;
    }
}

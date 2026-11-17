<?php
declare(strict_types=1);

namespace App\Shared\Storage;

/**
 * Cliente mínimo para la API de Cloudinary.
 *
 * Usa la misma credencial que el proyecto Carwash:
 * CLOUDINARY_URL=cloudinary://<api_key>:<api_secret>@<cloud_name>
 *
 * La subida y el borrado se firman con SHA-1 (no se expone el secreto).
 */
final class CloudinaryClient
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $cloudinaryUrl)
    {
        $parts = parse_url(trim($cloudinaryUrl));

        if (
            !is_array($parts)
            || ($parts['scheme'] ?? '') !== 'cloudinary'
            || empty($parts['host'])
            || empty($parts['user'])
            || empty($parts['pass'])
        ) {
            throw new \RuntimeException('La credencial de Cloudinary no tiene el formato esperado.');
        }

        $this->cloudName = (string) $parts['host'];
        $this->apiKey = (string) $parts['user'];
        $this->apiSecret = (string) $parts['pass'];
    }

    /**
     * @return array{secure_url: string, public_id: string}
     */
    public function upload(string $path, string $folder, string $publicId, string $mimeType): array
    {
        $timestamp = time();
        $params = [
            'folder' => trim($folder, '/'),
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];
        $params['signature'] = $this->signature($params);
        $params['api_key'] = $this->apiKey;
        $params['file'] = new \CURLFile($path, $mimeType, basename($path));

        $response = $this->request('/image/upload', $params);

        if (!isset($response['secure_url'], $response['public_id'])) {
            throw new \RuntimeException('Cloudinary no devolvió la URL de la imagen.');
        }

        return [
            'secure_url' => (string) $response['secure_url'],
            'public_id' => (string) $response['public_id'],
        ];
    }

    public function destroy(string $publicId): bool
    {
        if ($publicId === '') {
            return false;
        }

        $timestamp = time();
        $params = [
            'invalidate' => '1',
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];
        $params['signature'] = $this->signature($params);
        $params['api_key'] = $this->apiKey;

        $response = $this->request('/image/destroy', $params);

        return ($response['result'] ?? '') === 'ok';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function signature(array $params): string
    {
        unset($params['file'], $params['api_key'], $params['signature']);
        ksort($params);

        $pairs = [];

        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return sha1(implode('&', $pairs) . $this->apiSecret);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $params): array
    {
        $url = 'https://api.cloudinary.com/v1_1/' . rawurlencode($this->cloudName) . $endpoint;
        $handle = curl_init($url);

        if ($handle === false) {
            throw new \RuntimeException('No se pudo iniciar la conexión con Cloudinary.');
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($body === false) {
            throw new \RuntimeException('No se pudo conectar con Cloudinary' . ($error !== '' ? ': ' . $error : '.'));
        }

        $decoded = json_decode((string) $body, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Cloudinary devolvió una respuesta no válida.');
        }

        if ($status < 200 || $status >= 300) {
            $message = '';

            if (isset($decoded['error']) && is_array($decoded['error'])) {
                $message = (string) ($decoded['error']['message'] ?? '');
            }

            throw new \RuntimeException(
                'Cloudinary rechazó la operación' . ($message !== '' ? ': ' . $message : ' (' . $status . ').')
            );
        }

        return $decoded;
    }
}

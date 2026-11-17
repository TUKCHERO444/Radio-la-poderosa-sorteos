<?php
declare(strict_types=1);

namespace App\Shared\Storage;

final class ImageStorage
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private string $driver;
    private string $baseFolder;
    private ?CloudinaryClient $cloudinary = null;

    public function __construct()
    {
        $this->driver = (string) config('media.driver', 'local');
        $this->baseFolder = trim((string) config('media.folder', 'radiolpsorteos'), '/');
    }

    /**
     * Guarda una imagen validada y devuelve la URL/ruta pública.
     *
     * @param array<string, mixed>|null $file
     */
    public function store(?array $file, string $folder): ?string
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('No se pudo subir la imagen.');
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \RuntimeException('La imagen debe pesar menos de 10 MB.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('El archivo recibido no es una imagen válida.');
        }

        $info = @getimagesize($tmp);

        if ($info === false) {
            throw new \RuntimeException('El archivo no es una imagen JPG, PNG o WebP válida.');
        }

        $allowed = [
            IMAGETYPE_JPEG => ['extension' => 'jpg', 'mime' => 'image/jpeg'],
            IMAGETYPE_PNG => ['extension' => 'png', 'mime' => 'image/png'],
            IMAGETYPE_WEBP => ['extension' => 'webp', 'mime' => 'image/webp'],
        ];

        $type = (int) ($info[2] ?? 0);

        if (!isset($allowed[$type])) {
            throw new \RuntimeException('Formato no permitido. Usa JPG, PNG o WebP.');
        }

        $folder = trim($folder, '/');

        if ($folder === '' || preg_match('/^[a-z0-9-]+$/', $folder) !== 1) {
            throw new \RuntimeException('Carpeta de destino no válida.');
        }

        if ($this->driver === 'cloudinary') {
            $publicId = bin2hex(random_bytes(16));
            $targetFolder = $this->baseFolder !== '' ? $this->baseFolder . '/' . $folder : $folder;
            $result = $this->cloudinary()->upload(
                $tmp,
                $targetFolder,
                $publicId,
                $allowed[$type]['mime']
            );

            return $result['secure_url'];
        }

        $directory = PUBLIC_ROOT . '/uploads/' . $folder;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('No se pudo crear la carpeta de imágenes.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$type]['extension'];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($tmp, $target)) {
            throw new \RuntimeException('No se pudo guardar la imagen.');
        }

        return '/uploads/' . $folder . '/' . $filename;
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (preg_match('~^https?://res\.cloudinary\.com/~i', $path) === 1) {
            $publicId = self::publicIdFromUrl($path);

            if ($publicId !== '') {
                try {
                    $deleted = $this->cloudinary()->destroy($publicId);

                    if ((bool) config('app.debug', false)) {
                        error_log('Cloudinary destroy ' . $publicId . ': ' . ($deleted ? 'ok' : 'not-ok'));
                    }
                } catch (\Throwable $exception) {
                    error_log('Cloudinary delete: ' . $exception->getMessage());
                }
            }

            return;
        }

        if (!str_starts_with($path, '/uploads/')) {
            return;
        }

        $full = PUBLIC_ROOT . $path;

        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * Extrae el public_id de una URL de Cloudinary sin transformaciones.
     * Ej.: https://res.cloudinary.com/demo/image/upload/v123/radiolpsorteos/prizes/abc.png
     *      -> radiolpsorteos/prizes/abc
     */
    public static function publicIdFromUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $marker = '/image/upload/';
        $position = strpos($path, $marker);

        if ($position === false) {
            return '';
        }

        $publicId = substr($path, $position + strlen($marker));
        $publicId = rawurldecode($publicId);

        if (preg_match('~^v[0-9]+/~', $publicId) === 1) {
            $publicId = (string) preg_replace('~^v[0-9]+/~', '', $publicId);
        }

        return (string) preg_replace('~\.[A-Za-z0-9]+$~', '', $publicId);
    }

    private function cloudinary(): CloudinaryClient
    {
        if ($this->cloudinary instanceof CloudinaryClient) {
            return $this->cloudinary;
        }

        $url = trim((string) config('media.cloudinary_url', ''));

        if ($url === '') {
            throw new \RuntimeException('Cloudinary no está configurado. Revisa MEDIA_CLOUDINARY_URL.');
        }

        $this->cloudinary = new CloudinaryClient($url);

        return $this->cloudinary;
    }
}

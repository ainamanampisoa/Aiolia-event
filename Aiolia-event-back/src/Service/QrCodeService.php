<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service pour générer des codes QR
 *
 * Utilise une API en ligne pour générer les QR codes (alternative simple)
 * Pour une solution plus professionnelle, installez: composer require endroid/qr-code
 */
class QrCodeService
{
    private string $publicPath;
    private RequestStack $requestStack;

    public function __construct(string $publicPath, RequestStack $requestStack)
    {
        $this->publicPath = $publicPath;
        $this->requestStack = $requestStack;
    }

    /**
     * Génère un code QR en utilisant une API en ligne (API QR Server)
     *
     * @param string $data Les données à encoder dans le QR code
     * @param int $size La taille du QR code (par défaut 300)
     * @return string L'URL de l'image du QR code
     */
    public function generateQrCodeUrl(string $data, int $size = 300): string
    {
        // Encoder les données pour l'URL
        $encodedData = urlencode($data);

        // Utiliser l'API QR Server (gratuite et sans limite)
        return sprintf(
            'https://api.qrserver.com/v1/create-qr-code/?size=%dx%d&data=%s',
            $size,
            $size,
            $encodedData
        );
    }

    /**
     * Génère un code QR et retourne le base64 de l'image
     *
     * @param string $data Les données à encoder dans le QR code
     * @param int $size La taille du QR code (par défaut 300)
     * @return string Le QR code en format base64
     * @throws \RuntimeException Si la génération échoue
     */
    public function generateQrCodeBase64(string $data, int $size = 300): string
    {
        $url = $this->generateQrCodeUrl($data, $size);
        $imageContent = @file_get_contents($url);

        if ($imageContent === false) {
            throw new \RuntimeException('Impossible de générer le QR code');
        }

        return base64_encode($imageContent);
    }

    /**
     * Génère un code QR et le sauvegarde dans un fichier
     *
     * @param string $data Les données à encoder dans le QR code
     * @param string $filename Le nom du fichier (sans extension)
     * @param string $path Le chemin relatif où sauvegarder (par défaut '/uploads/qrcodes/')
     * @param int $size La taille du QR code (par défaut 300)
     * @return string Le chemin relatif du fichier généré
     * @throws \RuntimeException Si la génération échoue
     */
    public function generateQrCodeFile(string $data, string $filename, string $path = '/uploads/qrcodes/', int $size = 300): string
    {
        // Créer le répertoire s'il n'existe pas
        $fullPath = $this->publicPath . $path;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // Générer le QR code
        $url = $this->generateQrCodeUrl($data, $size);
        $imageContent = @file_get_contents($url);

        if ($imageContent === false) {
            throw new \RuntimeException('Impossible de générer le QR code');
        }

        // Sauvegarder le fichier
        $filePath = $fullPath . $filename . '.png';
        file_put_contents($filePath, $imageContent);

        return $path . $filename . '.png';
    }

    /**
     * Génère un code QR pour un billet
     *
     * @param string $codeQr Le code QR stocké dans la base de données
     * @param int $size La taille du QR code (par défaut 300)
     * @return string L'URL du QR code
     */
    public function generateQrCodeForBillet(string $codeQr, int $size = 300): string
    {
        // Utiliser le code QR stocké dans la base de données
        return $this->generateQrCodeUrl($codeQr, $size);
    }

    /**
     * Génère un checksum pour un code QR (pour validation)
     *
     * @param string $data Les données du QR code
     * @return string Le checksum (SHA256)
     */
    public function generateChecksum(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Valide un code QR avec son checksum
     *
     * @param string $data Les données du QR code
     * @param string $checksum Le checksum à valider
     * @return bool True si le checksum est valide
     */
    public function validateChecksum(string $data, string $checksum): bool
    {
        return hash_equals($this->generateChecksum($data), $checksum);
    }
}


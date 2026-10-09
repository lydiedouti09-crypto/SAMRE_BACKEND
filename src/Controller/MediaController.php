<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MediaController extends AbstractController
{
    #[Route('/uploads/{path}', name: 'serve_uploads', requirements: ['path' => '.+'], methods: ['GET'])]
    #[Route('/api/uploads/{path}', name: 'serve_api_uploads', requirements: ['path' => '.+'], methods: ['GET'])]
    public function serveUpload(string $path): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        
        // Clean path against directory traversal
        $cleanPath = ltrim(str_replace(['../', '..\\'], '', $path), '/');
        
        $candidates = [
            $projectDir . '/public/uploads/' . $cleanPath,
            $projectDir . '/uploads/' . $cleanPath,
            $projectDir . '/public/' . $cleanPath,
            $projectDir . '/' . $cleanPath,
        ];

        foreach ($candidates as $filePath) {
            if (file_exists($filePath) && is_file($filePath)) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $mimes = [
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'webp' => 'image/webp',
                    'svg'  => 'image/svg+xml',
                    'gif'  => 'image/gif',
                    'ico'  => 'image/x-icon',
                ];
                $mimeType = $mimes[$ext] ?? (mime_content_type($filePath) ?: 'application/octet-stream');

                return new BinaryFileResponse($filePath, 200, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'public, max-age=86400',
                    'Access-Control-Allow-Origin' => '*',
                ]);
            }
        }

        return new JsonResponse(['error' => 'Fichier introuvable', 'path' => $cleanPath], 404);
    }
}

<?php

namespace App\Service;

use App\Entity\Client;
use Symfony\Component\String\Slugger\SluggerInterface;

class  FileUploaderService
{

    public function __construct(private string $uploadsDirectory)
    {
    }

    public function uploadMultipleFiles($files, Client $client): array
    {
        $uploadedFiles = [];
        foreach ($files as $file) {
            $uploadedFiles[] = $this->uploadFile($file, $client);
        }
        return $uploadedFiles;
    }

    public function uploadFile($file, Client $client): string
    {
        $directory = $this->directoryClient($client);

        //get the original file name
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName =($originalFilename).'-'.md5(uniqid()) . '.' . $file->guessExtension();
        $file->move($directory,$fileName);
        return $fileName;
    }

    private function directoryClient($client)
    {
        $directory = $this->uploadsDirectory . '/' . $client->getId();
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        return $directory;
    }

}
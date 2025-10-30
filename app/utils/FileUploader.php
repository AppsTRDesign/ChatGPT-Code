<?php
class FileUploader
{
    public static function uploadToCloudinary(array $file, array $config)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }

        $payload = [
            'file' => new CURLFile($file['tmp_name'], mime_content_type($file['tmp_name']), $file['name']),
            'upload_preset' => $config['preset'],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['cloudinary_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('Cloud upload failed: ' . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($response, true);
    }
}

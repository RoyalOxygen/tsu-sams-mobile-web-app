<?php
declare(strict_types=1);

namespace App\Services;

use App\Audit;
use App\Database;
use App\Security;

final class FaceService
{
    public static function baseUrl(): string
    {
        return rtrim((string)config('face.url', 'http://127.0.0.1:9000'), '/');
    }

    public static function healthy(): bool
    {
        $res = self::http('GET', '/health');
        return !empty($res['ok']) && (($res['json']['status'] ?? '') === 'healthy');
    }

    public static function enroll(int $studentId, array $imageBinaries, ?string $samplePath = null): array
    {
        $files = [];
        foreach ($imageBinaries as $i => $bin) {
            if (!is_string($bin) || $bin === '') {
                continue;
            }
            $files[] = ['name' => 'face_images', 'filename' => 'face' . $i . '.jpg', 'body' => $bin];
        }
        if (!$files) {
            return ['ok' => false, 'message' => 'No face images captured.'];
        }
        $res = self::http('POST', '/enroll', [
            'fields' => ['student_id' => (string)$studentId],
            'files' => $files,
        ]);
        if (!$res['ok']) {
            return ['ok' => false, 'message' => $res['error'] ?? 'Face service unavailable.'];
        }
        $json = $res['json'] ?? [];
        if (empty($json['success'])) {
            return ['ok' => false, 'message' => (string)($json['message'] ?? 'No valid face found.')];
        }
        $embedding = $json['embedding'] ?? [];
        if (is_array($embedding) && count($embedding) >= 128) {
            self::storeDescriptor($studentId, $embedding, $samplePath);
        } else {
            self::storeDescriptor($studentId, [0.0], $samplePath);
        }
        Audit::log('face.enroll', 'students', (string)$studentId, [
            'confidence' => $json['confidence'] ?? null,
            'dim' => $json['embedding_length'] ?? null,
        ]);
        return [
            'ok' => true,
            'message' => (string)($json['message'] ?? 'Face enrolled successfully'),
            'confidence' => (float)($json['confidence'] ?? 0),
            'embedding' => $embedding,
        ];
    }

    public static function verifyImage(string $imageBinary): array
    {
        $res = self::http('POST', '/verify', [
            'files' => [['name' => 'image', 'filename' => 'live.jpg', 'body' => $imageBinary]],
        ]);
        if (!$res['ok']) {
            return ['ok' => false, 'score' => 0, 'reason' => $res['error'] ?? 'Face service unavailable.'];
        }
        $json = $res['json'] ?? [];
        if (empty($json['success'])) {
            return [
                'ok' => false,
                'score' => (float)($json['distance'] ?? 1),
                'confidence' => (float)($json['confidence'] ?? 0),
                'reason' => (string)($json['message'] ?? 'Face not recognized.'),
            ];
        }
        return [
            'ok' => true,
            'student_id' => (int)($json['student_id'] ?? 0),
            'score' => (float)($json['distance'] ?? 0),
            'confidence' => (float)($json['confidence'] ?? 0),
            'reason' => (string)($json['message'] ?? 'Face verified'),
        ];
    }

    public static function matchStudent(int $studentId, string $imageBinary): array
    {
        $stored = self::loadDescriptor($studentId);
        $extract = self::extract($imageBinary);
        if (!$extract['ok']) {
            return ['ok' => false, 'score' => 0, 'reason' => $extract['message']];
        }
        if ($stored && count($stored) >= 128) {
            $cmp = self::compare($stored, $extract['descriptor']);
            if ($cmp['ok']) {
                return $cmp;
            }
        }
        $oneN = self::verifyImage($imageBinary);
        if ($oneN['ok'] && (int)$oneN['student_id'] === $studentId) {
            return [
                'ok' => true,
                'score' => $oneN['score'],
                'confidence' => $oneN['confidence'] ?? 0,
                'reason' => 'Face verified',
            ];
        }
        if ($oneN['ok']) {
            return ['ok' => false, 'score' => $oneN['score'], 'reason' => 'Face does not match this student.'];
        }
        return [
            'ok' => false,
            'score' => $oneN['score'] ?? 1,
            'reason' => $oneN['reason'] ?? 'Face does not match enrolled template.',
        ];
    }

    public static function extract(string $imageBinary): array
    {
        $res = self::http('POST', '/extract', [
            'files' => [['name' => 'image', 'filename' => 'face.jpg', 'body' => $imageBinary]],
        ]);
        if (!$res['ok']) {
            return ['ok' => false, 'message' => $res['error'] ?? 'Face service unavailable.'];
        }
        $json = $res['json'] ?? [];
        if (empty($json['success']) || empty($json['descriptor'])) {
            return ['ok' => false, 'message' => (string)($json['message'] ?? 'No face detected.')];
        }
        return [
            'ok' => true,
            'descriptor' => $json['descriptor'],
            'confidence' => (float)($json['confidence'] ?? 0),
            'message' => (string)$json['message'],
        ];
    }

    public static function compare(array $a, array $b): array
    {
        $res = self::http('POST', '/compare', [
            'json' => ['descriptor1' => array_map('floatval', $a), 'descriptor2' => array_map('floatval', $b)],
        ]);
        if (!$res['ok']) {
            return ['ok' => false, 'score' => 1, 'reason' => $res['error'] ?? 'Compare failed.'];
        }
        $json = $res['json'] ?? [];
        $matched = !empty($json['matched']);
        return [
            'ok' => $matched,
            'score' => (float)($json['cosine_similarity'] ?? 0),
            'confidence' => (float)($json['confidence'] ?? 0),
            'reason' => $matched ? 'Face verified' : 'Face does not match enrolled template.',
        ];
    }

    public static function deleteEnrollment(int $studentId): void
    {
        self::http('DELETE', '/delete/' . $studentId);
    }

    public static function storeDescriptor(int $studentId, array $descriptor, ?string $samplePath = null): void
    {
        $json = json_encode(array_map('floatval', $descriptor));
        $enc = Security::encrypt($json);
        $hash = hash('sha256', $json);
        $existing = Database::one('SELECT id FROM face_profiles WHERE student_id = ?', [$studentId]);
        if ($existing) {
            Database::update('face_profiles', [
                'descriptor_enc' => $enc,
                'descriptor_hash' => $hash,
                'sample_path' => $samplePath,
                'updated_at' => now(),
            ], 'student_id = ?', [$studentId]);
        } else {
            Database::insert('face_profiles', [
                'student_id' => $studentId,
                'descriptor_enc' => $enc,
                'descriptor_hash' => $hash,
                'sample_path' => $samplePath,
                'enrolled_at' => now(),
            ]);
        }
    }

    public static function loadDescriptor(int $studentId): ?array
    {
        $row = Database::one('SELECT descriptor_enc FROM face_profiles WHERE student_id = ?', [$studentId]);
        if (!$row) {
            return null;
        }
        $json = Security::decrypt($row['descriptor_enc']);
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public static function saveSampleFromDataUrl(string $dataUrl, int $studentId): ?string
    {
        $bin = self::decodeImage($dataUrl);
        if ($bin === null) {
            return null;
        }
        $dir = rtrim((string)config('paths.uploads'), '/') . '/faces';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'face_' . $studentId . '_' . bin2hex(random_bytes(4)) . '.jpg';
        file_put_contents($dir . '/' . $name, $bin);
        return 'uploads/faces/' . $name;
    }

    public static function decodeImage(string $payload): ?string
    {
        $payload = trim($payload);
        if ($payload === '') {
            return null;
        }
        if (preg_match('#^data:image/(jpeg|jpg|png|webp);base64,(.+)$#i', $payload, $m)) {
            $bin = base64_decode($m[2], true);
            return ($bin !== false && strlen($bin) > 32) ? $bin : null;
        }
        $bin = base64_decode($payload, true);
        if ($bin !== false && strlen($bin) > 32 && strncmp($bin, "\xFF\xD8", 2) === 0) {
            return $bin;
        }
        return null;
    }

    public static function imagesFromRequest(): array
    {
        $out = [];
        if (!empty($_FILES['face_images'])) {
            $names = $_FILES['face_images']['name'] ?? [];
            if (!is_array($names)) {
                if (($_FILES['face_images']['error'] ?? 1) === UPLOAD_ERR_OK) {
                    $out[] = (string)file_get_contents($_FILES['face_images']['tmp_name']);
                }
            } else {
                foreach ($_FILES['face_images']['tmp_name'] as $i => $tmp) {
                    if (($_FILES['face_images']['error'][$i] ?? 1) === UPLOAD_ERR_OK && is_readable($tmp)) {
                        $out[] = (string)file_get_contents($tmp);
                    }
                }
            }
        }
        foreach (['snapshot', 'image'] as $key) {
            if (!empty($_FILES[$key]) && ($_FILES[$key]['error'] ?? 1) === UPLOAD_ERR_OK) {
                $out[] = (string)file_get_contents($_FILES[$key]['tmp_name']);
            }
        }
        $snaps = $_POST['snapshots'] ?? $_POST['snapshot'] ?? [];
        if (isset($_POST['snapshots']) && is_array($_POST['snapshots'])) {
            $snaps = $_POST['snapshots'];
        }
        if (is_string($snaps)) {
            $snaps = [$snaps];
        }
        if (is_array($snaps)) {
            foreach ($snaps as $s) {
                $bin = self::decodeImage((string)$s);
                if ($bin !== null) {
                    $out[] = $bin;
                }
            }
        }
        return array_values(array_filter($out, static fn($b) => is_string($b) && strlen($b) > 32));
    }

    /**
     * @param array{fields?:array<string,string>,files?:list<array{name:string,filename:string,body:string}>,json?:array}|null $opts
     * @return array{ok:bool,json?:array,error?:string,status?:int}
     */
    private static function http(string $method, string $path, ?array $opts = null): array
    {
        $url = self::baseUrl() . $path;
        $timeout = (int)config('face.timeout', 60);
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'PHP cURL is required to reach the face service.'];
        }
        $ch = curl_init($url);
        $headers = ['Accept: application/json'];
        $method = strtoupper($method);
        if ($method === 'POST' && isset($opts['json'])) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opts['json']));
        } elseif ($method === 'POST') {
            $boundary = '----TSUFace' . bin2hex(random_bytes(8));
            $headers[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
            curl_setopt($ch, CURLOPT_POSTFIELDS, self::buildMultipart($boundary, $opts['fields'] ?? [], $opts['files'] ?? []));
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            return ['ok' => false, 'error' => 'Face service unreachable: ' . $err, 'status' => $status];
        }
        $json = json_decode((string)$raw, true);
        if ($status >= 400) {
            $msg = is_array($json) ? (string)($json['detail'] ?? $json['message'] ?? 'Face service error') : 'Face service error';
            if (is_array($json) && isset($json['detail']) && is_array($json['detail'])) {
                $msg = json_encode($json['detail']);
            }
            return ['ok' => false, 'error' => $msg, 'status' => $status, 'json' => is_array($json) ? $json : []];
        }
        return ['ok' => true, 'json' => is_array($json) ? $json : [], 'status' => $status];
    }

    /**
     * @param array<string,string> $fields
     * @param list<array{name:string,filename:string,body:string}> $files
     */
    private static function buildMultipart(string $boundary, array $fields, array $files): string
    {
        $eol = "\r\n";
        $body = '';
        foreach ($fields as $name => $value) {
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
            $body .= $value . $eol;
        }
        foreach ($files as $f) {
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Disposition: form-data; name="' . $f['name'] . '"; filename="' . $f['filename'] . '"' . $eol;
            $body .= 'Content-Type: image/jpeg' . $eol . $eol;
            $body .= $f['body'] . $eol;
        }
        $body .= '--' . $boundary . '--' . $eol;
        return $body;
    }
}

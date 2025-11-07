<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Member;

final class MemberController extends Controller
{
    public function index(): void
    {
        $this->view('admin/members', [
            'title' => 'Üye Yönetimi',
            'members' => Member::all(),
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        $data = [
            'telegram_id' => trim($_POST['telegram_id'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
            'joined_from_channel' => trim($_POST['joined_from_channel'] ?? ''),
            'last_active_at' => trim($_POST['last_active_at'] ?? ''),
            'online_status' => trim($_POST['online_status'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        Member::create($data);
        $this->json(['status' => 'success']);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        Member::delete($id);
        $this->json(['status' => 'success']);
    }

    public function export(): void
    {
        $members = Member::all();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="members.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($members[0] ?? [
            'id' => 'ID',
            'telegram_id' => 'Telegram ID',
            'username' => 'Username',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'is_public' => 'Public',
            'joined_from_channel' => 'Joined From',
            'last_active_at' => 'Last Active',
            'online_status' => 'Online Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ]));

        foreach ($members as $member) {
            fputcsv($output, $member);
        }
        fclose($output);
        exit;
    }

    public function import(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            $this->json(['status' => 'error', 'message' => 'Geçersiz istek.'], 422);
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['status' => 'error', 'message' => 'Dosya yüklenemedi.'], 400);
        }

        $handle = fopen($_FILES['file']['tmp_name'], 'rb');
        if (!$handle) {
            $this->json(['status' => 'error', 'message' => 'Dosya açılamadı.'], 400);
        }

        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }

            Member::create([
                'telegram_id' => $data['telegram_id'] ?? '',
                'username' => $data['username'] ?? '',
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'is_public' => isset($data['is_public']) ? (int) $data['is_public'] : 0,
                'joined_from_channel' => $data['joined_from_channel'] ?? '',
                'last_active_at' => $data['last_active_at'] ?? '',
                'online_status' => $data['online_status'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        fclose($handle);

        $this->json(['status' => 'success']);
    }
}

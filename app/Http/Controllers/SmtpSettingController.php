<?php

namespace App\Http\Controllers;

use App\Models\SmtpSetting;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;

class SmtpSettingController extends Controller
{
    public function index()
    {
        $smtp = SmtpSetting::getSettings() ?? new SmtpSetting([
            'host'       => 'smtp.gmail.com',
            'port'       => 587,
            'encryption' => 'tls',
            'username'   => '',
            'password'   => '',
            'from_name'  => 'MailFlow',
            'from_email' => '',
        ]);

        $hasPassword = (bool) SmtpSetting::getSettings()?->getRawOriginal('password');

        return view('smtp-settings.index', compact('smtp', 'hasPassword'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'host'       => 'required|string',
            'port'       => 'required|integer|min:1|max:65535',
            'encryption' => 'required|in:tls,ssl,none',
            'username'   => 'required|string',
            'password'   => 'nullable|string',
            'from_name'  => 'required|string|max:150',
            'from_email' => 'required|email',
        ]);

        $existing = SmtpSetting::getSettings();

        if (empty($data['password']) && $request->boolean('has_existing_password') && $existing) {
            $data['password'] = $existing->getRawOriginal('password');
        }

        if ($existing) {
            $existing->update($data);
        } else {
            SmtpSetting::create($data);
        }

        return back()->with('success', 'SMTP configuration saved successfully.');
    }

    public function test()
    {
        $smtp = SmtpSetting::getSettings();

        if (!$smtp) {
            return back()->with('error', 'No SMTP settings found.');
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host     = $smtp->host;
            $mail->Port     = (int) $smtp->port;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp->username;
            $mail->Password = $smtp->password;

            if ($smtp->encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($smtp->encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }

            if (!$mail->smtpConnect()) {
                return back()->with('error', 'Could not connect to SMTP server.');
            }
            $mail->smtpClose();

            return back()->with('success', 'SMTP connection successful.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

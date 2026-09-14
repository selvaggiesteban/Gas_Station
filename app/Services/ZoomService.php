<?php

namespace App\Services;

use App\Models\ZoomMeeting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    private string $accountId;
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->accountId = config('services.zoom.account_id', '');
        $this->clientId = config('services.zoom.client_id', '');
        $this->clientSecret = config('services.zoom.client_secret', '');
        $this->baseUrl = 'https://api.zoom.us/v2';
    }

    /**
     * Get OAuth access token
     */
    private function getAccessToken(): string
    {
        $cacheKey = 'zoom_access_token_' . $this->accountId;
        
        $cachedToken = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->accountId,
                ]);

            if (!$response->successful()) {
                Log::error('Zoom OAuth failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to get Zoom access token');
            }

            $data = $response->json();
            $token = $data['access_token'];
            $expiresIn = $data['expires_in'] - 60; // Buffer of 1 minute

            \Illuminate\Support\Facades\Cache::put($cacheKey, $token, $expiresIn);

            return $token;

        } catch (\Exception $e) {
            Log::error('Zoom OAuth error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create a new Zoom meeting
     */
    public function createMeeting(array $data): array
    {
        try {
            $token = $this->getAccessToken();

            $meetingData = [
                'topic' => $data['title'] ?? 'Clase en directo - Academicus',
                'type' => 2, // Scheduled meeting
                'start_time' => $data['start_time']->format('Y-m-d\TH:i:s'),
                'duration' => $data['duration_minutes'] ?? 60,
                'timezone' => $data['timezone'] ?? 'Europe/Madrid',
                'password' => $data['password'] ?? $this->generatePassword(),
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'join_before_host' => false,
                    'mute_upon_entry' => true,
                    'waiting_room' => true,
                    'recording' => 'cloud',
                    'auto_recording' => 'cloud',
                ],
            ];

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/users/me/meetings", $meetingData);

            if (!$response->successful()) {
                Log::error('Failed to create Zoom meeting', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to create meeting',
                ];
            }

            $meeting = $response->json();

            Log::info('Zoom meeting created', [
                'meeting_id' => $meeting['id'],
                'topic' => $meetingData['topic'],
            ]);

            return [
                'success' => true,
                'meeting_id' => $meeting['id'],
                'join_url' => $meeting['join_url'],
                'start_url' => $meeting['start_url'],
                'password' => $meeting['password'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Create Zoom meeting error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing Zoom meeting
     */
    public function updateMeeting(string $zoomMeetingId, array $data): array
    {
        try {
            $token = $this->getAccessToken();

            $updateData = [
                'topic' => $data['title'] ?? null,
                'start_time' => isset($data['start_time']) ? $data['start_time']->format('Y-m-d\TH:i:s') : null,
                'duration' => $data['duration_minutes'] ?? null,
                'timezone' => $data['timezone'] ?? null,
            ];

            // Remove null values
            $updateData = array_filter($updateData);

            $response = Http::withToken($token)
                ->patch("{$this->baseUrl}/meetings/{$zoomMeetingId}", $updateData);

            if (!$response->successful()) {
                Log::error('Failed to update Zoom meeting', [
                    'meeting_id' => $zoomMeetingId,
                    'status' => $response->status(),
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to update meeting',
                ];
            }

            Log::info('Zoom meeting updated', ['meeting_id' => $zoomMeetingId]);

            return [
                'success' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Update Zoom meeting error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a Zoom meeting
     */
    public function deleteMeeting(string $zoomMeetingId): bool
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->delete("{$this->baseUrl}/meetings/{$zoomMeetingId}");

            if (!$response->successful() && $response->status() !== 404) {
                Log::error('Failed to delete Zoom meeting', [
                    'meeting_id' => $zoomMeetingId,
                    'status' => $response->status(),
                ]);
                return false;
            }

            Log::info('Zoom meeting deleted', ['meeting_id' => $zoomMeetingId]);

            return true;

        } catch (\Exception $e) {
            Log::error('Delete Zoom meeting error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get meeting details
     */
    public function getMeeting(string $zoomMeetingId): ?array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/meetings/{$zoomMeetingId}");

            if (!$response->successful()) {
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Get Zoom meeting error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get recording for a meeting
     */
    public function getRecording(string $zoomMeetingId): ?array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/meetings/{$zoomMeetingId}/recordings");

            if (!$response->successful()) {
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Get Zoom recording error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate a random password for meetings
     */
    private function generatePassword(): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    }

    /**
     * Check if Zoom is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->accountId) && !empty($this->clientId) && !empty($this->clientSecret);
    }
}
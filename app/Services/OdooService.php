<?php

namespace App\Services;

use Ripcord\Ripcord;
use Illuminate\Support\Facades\Log;
use App\Models\OdooSyncLog;
use Exception;

class OdooService
{
    protected $url;
    protected $db;
    protected $uid;
    protected $password;
    protected $models;
    protected $common;

    public function __construct()
    {
        $this->url = config('odoo.url');
        $this->db = config('odoo.database');
        $this->password = config('odoo.password');

        try {
            $this->authenticate();
        } catch (Exception $e) {
            Log::error('Odoo authentication failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Authenticate with Odoo
     */
    protected function authenticate()
    {
        $this->common = Ripcord::client("{$this->url}/xmlrpc/2/common");

        $this->uid = $this->common->authenticate(
            $this->db,
            config('odoo.username'),
            $this->password,
            []
        );

        if (!$this->uid) {
            throw new Exception('Odoo authentication failed');
        }

        $this->models = Ripcord::client("{$this->url}/xmlrpc/2/object");

        Log::info('Odoo authentication successful', ['uid' => $this->uid]);
    }

    /**
     * Search and read records from Odoo
     *
     * @param string $model
     * @param array $domain
     * @param array $fields
     * @param int|null $limit
     * @return array
     */
    public function search($model, array $domain = [], array $fields = [], $limit = null)
    {
        try {
            $params = ['limit' => $limit];
            if ($limit === null) {
                unset($params['limit']);
            }

            $ids = $this->models->execute_kw(
                $this->db,
                $this->uid,
                $this->password,
                $model,
                'search',
                [$domain],
                $params
            );

            if (empty($ids)) {
                return [];
            }

            return $this->read($model, $ids, $fields);

        } catch (Exception $e) {
            $this->logSync($model, null, 'search', 'from_odoo', 'error', $domain, null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Read records from Odoo by IDs
     *
     * @param string $model
     * @param array|int $ids
     * @param array $fields
     * @return array
     */
    public function read($model, $ids, array $fields = [])
    {
        try {
            if (!is_array($ids)) {
                $ids = [$ids];
            }

            $records = $this->models->execute_kw(
                $this->db,
                $this->uid,
                $this->password,
                $model,
                'read',
                [$ids],
                ['fields' => $fields]
            );

            $this->logSync($model, null, 'read', 'from_odoo', 'success', ['ids' => $ids], $records);

            return $records;

        } catch (Exception $e) {
            $this->logSync($model, null, 'read', 'from_odoo', 'error', ['ids' => $ids], null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new record in Odoo
     *
     * @param string $model
     * @param array $data
     * @return int
     */
    public function create($model, array $data)
    {
        try {
            $id = $this->models->execute_kw(
                $this->db,
                $this->uid,
                $this->password,
                $model,
                'create',
                [$data]
            );

            $this->logSync($model, $id, 'create', 'to_odoo', 'success', $data, ['id' => $id]);

            Log::info("Created record in Odoo", ['model' => $model, 'id' => $id]);

            return $id;

        } catch (Exception $e) {
            $this->logSync($model, null, 'create', 'to_odoo', 'error', $data, null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a record in Odoo
     *
     * @param string $model
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($model, $id, array $data)
    {
        try {
            $result = $this->models->execute_kw(
                $this->db,
                $this->uid,
                $this->password,
                $model,
                'write',
                [[$id], $data]
            );

            $this->logSync($model, $id, 'update', 'to_odoo', 'success', $data, ['result' => $result]);

            Log::info("Updated record in Odoo", ['model' => $model, 'id' => $id]);

            return $result;

        } catch (Exception $e) {
            $this->logSync($model, $id, 'update', 'to_odoo', 'error', $data, null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a record from Odoo
     *
     * @param string $model
     * @param int $id
     * @return bool
     */
    public function delete($model, $id)
    {
        try {
            $result = $this->models->execute_kw(
                $this->db,
                $this->uid,
                $this->password,
                $model,
                'unlink',
                [[$id]]
            );

            $this->logSync($model, $id, 'delete', 'to_odoo', 'success', [], ['result' => $result]);

            Log::info("Deleted record from Odoo", ['model' => $model, 'id' => $id]);

            return $result;

        } catch (Exception $e) {
            $this->logSync($model, $id, 'delete', 'to_odoo', 'error', [], null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if connection to Odoo is working
     *
     * @return bool
     */
    public function testConnection()
    {
        try {
            $version = $this->common->version();
            Log::info('Odoo connection test successful', ['version' => $version]);
            return true;
        } catch (Exception $e) {
            Log::error('Odoo connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log synchronization operation
     *
     * @param string $model
     * @param int|null $recordId
     * @param string $operation
     * @param string $direction
     * @param string $status
     * @param array|null $requestData
     * @param array|null $responseData
     * @param string|null $errorMessage
     */
    protected function logSync($model, $recordId, $operation, $direction, $status, $requestData, $responseData, $errorMessage = null)
    {
        try {
            OdooSyncLog::create([
                'model' => $model,
                'record_id' => $recordId,
                'operation' => $operation,
                'direction' => $direction,
                'status' => $status,
                'request_data' => $requestData,
                'response_data' => $responseData,
                'error_message' => $errorMessage,
                'synced_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log Odoo sync: ' . $e->getMessage());
        }
    }

    /**
     * Get Odoo model name from config
     *
     * @param string $key
     * @return string
     */
    public function getModelName($key)
    {
        return config("odoo.models.{$key}");
    }
}

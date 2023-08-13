<?php

namespace Correios\Services\Authorization;

use Correios\Exceptions\ApiRequestException;
use Correios\Services\AbstractRequest;

class Authentication extends AbstractRequest
{
    private string $username;
    private string $password;
    private string $contract;
    private string $token;
    private \DateTime $tokenExpiration;
    public function __construct(string $username, string $password, string $contract, bool $isTestMode = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->contract = $contract;

        $this->setEnvironment($isTestMode ? 'sandbox' : 'production');
        $this->setEndpoint('token/v1/autentica/contrato');
        $this->setMethod('POST');

        $this->buildBody();
        $this->buildHeaders();
    }

    private function buildBody(): void
    {
        $this->setBody([
            'numero' => $this->contract
        ]);
    }

    private function buildHeaders(): void
    {
        $this->setHeaders([
            'Authorization' => 'Basic ' . base64_encode("$this->username:$this->password")
        ]);
    }

    public function generateToken(): void
    {
        try {
            $response = $this->handleRequest();

            if (!isset($response['data'])) {
                return;
            }

            $data = $response['data'];

            if (isset($data->token) && isset($data->expiraEm)) {
                $this->token = $data->token;
                $this->tokenExpiration = new \DateTime($data->expiraEm);
            }

        } catch (ApiRequestException $e) {
            $this->errors[$e->getCode()] = $e->getMessage();
        }
    }
    public function getToken(): string
    {
        return $this->token ?? '';
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getTokenExpiration(): \DateTime
    {
        return $this->tokenExpiration ?? new \DateTime();
    }

    public function handleRequest(): array
    {
        $this->sendRequest();

        return [
            'code' => $this->getResponseCode(),
            'date' => $this->getResponseBody()
        ];
    }
}

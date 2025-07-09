<?php

namespace App\Http;

/**
 * Generic API client for making HTTP requests
 */
class ApiClient
{
    /**
     * @var array Default headers to include with every request
     */
    private array $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    /**
     * Constructor
     * 
     * @param string|null $baseUrl Base URL for all API requests
     */
    public function __construct(
        private ?string $baseUrl = null
    ) {}

    /**
     * Set the base URL for API requests
     * 
     * @param string $baseUrl
     * @return self
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Add default headers to be sent with every request
     * 
     * @param array $headers
     * @return self
     */
    public function addDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    /**
     * Make a GET request to the API
     * 
     * @param string $endpoint The API endpoint
     * @param array $queryParams Optional query parameters
     * @param array $headers Optional additional headers
     * @return array The decoded JSON response
     * @throws \RuntimeException If the request fails
     */
    public function get(string $endpoint, array $queryParams = [], array $headers = []): array
    {
        $url = $this->buildUrl($endpoint, $queryParams);
        return $this->request($url, 'GET', null, $headers);
    }

    /**
     * Make a POST request to the API
     * 
     * @param string $endpoint The API endpoint
     * @param array|null $data The request body data
     * @param array $queryParams Optional query parameters
     * @param array $headers Optional additional headers
     * @return array The decoded JSON response
     * @throws \RuntimeException If the request fails
     */
    public function post(string $endpoint, ?array $data = null, array $queryParams = [], array $headers = []): array
    {
        $url = $this->buildUrl($endpoint, $queryParams);
        return $this->request($url, 'POST', $data, $headers);
    }

    /**
     * Make a PUT request to the API
     * 
     * @param string $endpoint The API endpoint
     * @param array|null $data The request body data
     * @param array $queryParams Optional query parameters
     * @param array $headers Optional additional headers
     * @return array The decoded JSON response
     * @throws \RuntimeException If the request fails
     */
    public function put(string $endpoint, ?array $data = null, array $queryParams = [], array $headers = []): array
    {
        $url = $this->buildUrl($endpoint, $queryParams);
        return $this->request($url, 'PUT', $data, $headers);
    }

    /**
     * Make a DELETE request to the API
     * 
     * @param string $endpoint The API endpoint
     * @param array $queryParams Optional query parameters
     * @param array $headers Optional additional headers
     * @return array The decoded JSON response
     * @throws \RuntimeException If the request fails
     */
    public function delete(string $endpoint, array $queryParams = [], array $headers = []): array
    {
        $url = $this->buildUrl($endpoint, $queryParams);
        return $this->request($url, 'DELETE', null, $headers);
    }

    /**
     * Build a URL with query parameters
     * 
     * @param string $endpoint
     * @param array $queryParams
     * @return string
     */
    private function buildUrl(string $endpoint, array $queryParams = []): string
    {
        $url = $this->baseUrl ? rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/') : $endpoint;
        
        if (!empty($queryParams)) {
            $url .= (strpos($url, '?') === false) ? '?' : '&';
            $url .= http_build_query($queryParams);
        }
        
        return $url;
    }

    /**
     * Make an HTTP request
     * 
     * @param string $url The full URL
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array|null $data Optional request body data
     * @param array $headers Optional additional headers
     * @return array The decoded JSON response
     * @throws \RuntimeException If the request fails
     */
    private function request(string $url, string $method, ?array $data = null, array $headers = []): array
    {
        $curl = curl_init();
        
        $allHeaders = array_merge($this->defaultHeaders, $headers);
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $allHeaders,
        ];
        
        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($err) {
            throw new \RuntimeException('cURL Error: ' . $err);
        }
        
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException('API Error: Received status code ' . $statusCode . '. Response: ' . $response);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $decodedResponse;
    }
}

<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Dataset",
 *     type="object",
 *     title="Conjunto de Datos",
 *     description="Representa un conjunto de datos en CKAN",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         description="ID único del conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nombre del conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="notes",
 *         type="string",
 *         description="Descripción o notas sobre el conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="resource_formats",
 *         type="array",
 *         @OA\Items(type="string"),
 *         description="Formatos de los recursos disponibles para el conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="api_detail_url",
 *         type="string",
 *         description="URL de la API para obtener detalles del conjunto de datos"
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="DatasetDetail",
 *     type="object",
 *     title="Detalle del Conjunto de Datos",
 *     description="Detalles de un conjunto de datos específico en CKAN",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         description="ID único del conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nombre técnico del conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Título del conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="notes",
 *         type="string",
 *         description="Descripción o notas sobre el conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         description="URL del conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="num_resources",
 *         type="integer",
 *         description="Número de recursos disponibles en el conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="metadata_created",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de creación del metadato"
 *     ),
 *     @OA\Property(
 *         property="metadata_modified",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de la última modificación del metadato"
 *     ),
 *     @OA\Property(
 *         property="resources",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Resource"),
 *         description="Recursos asociados al conjunto de datos"
 *     ),
 *     @OA\Property(
 *         property="tags",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Tag"),
 *         description="Etiquetas asociadas al conjunto de datos"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Resource",
 *     type="object",
 *     title="Recurso",
 *     description="Representa un recurso dentro de un conjunto de datos en CKAN"
 * )
 *
 * @OA\Schema(
 *     schema="Tag",
 *     type="object",
 *     title="Etiqueta",
 *     description="Representa una etiqueta asociada a un conjunto de datos en CKAN",
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nombre técnico de la etiqueta"
 *     ),
 *     @OA\Property(
 *         property="display_name",
 *         type="string",
 *         description="Nombre visible de la etiqueta"
 *     )
 * )
 */
class CkanApiController extends Controller
{
    public $client;
    protected $baseUrl;
    protected $elasticsearch;

    public function __construct()
    {
        $this->client = new Client(['verify' => false]);
        $this->baseUrl = env('CKAN_BASE_URL', 'https://your-ckan-instance/api/3/action');

        $this->elasticsearch = ClientBuilder::create()
            ->setHosts([env('ELASTIC_SEARCH_URL', 'https://your-elastic-search-instance:9200')])
            ->build();
    }

    /**
     * @OA\Get(
     *     path="/ckan-datasets",
     *     summary="Obtiene conjuntos de datos",
     *     description="Recupera una lista de conjuntos de datos desde CKAN, con la opción de paginación.",
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Índice de inicio para la paginación de los conjuntos de datos",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="rows",
     *         in="query",
     *         required=false,
     *         description="Número de filas o conjuntos de datos a devolver",
     *         @OA\Schema(type="integer", default=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="count",
     *                 type="integer",
     *                 description="Número total de conjuntos de datos disponibles"
     *             ),
     *             @OA\Property(
     *                 property="datasets",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Dataset"),
     *                 description="Lista de conjuntos de datos"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud"
     *     )
     * )
     */
    public function fetchDataset($start = 0, $rows = 100)
    {
        $searchResults = $this->makeRequest("package_search?start=$start&rows=$rows");
        if (!isset($searchResults['error'])) {
            return [
                'count' => $searchResults['count'],
                'datasets' => $this->processCkanResults($searchResults['results'])
            ];
        } else {
            return $searchResults;
        }
    }
    /**
     * @OA\Get(
     *     path="/ckan-datasets/details/{id}",
     *     summary="Obtiene detalles de un conjunto de datos específico",
     *     description="Recupera los detalles completos de un conjunto de datos específico en CKAN, incluyendo recursos y etiquetas.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID único del conjunto de datos",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del conjunto de datos",
     *         @OA\JsonContent(ref="#/components/schemas/DatasetDetail")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Conjunto de datos no encontrado"
     *     )
     * )
     */
    public function fetchDatasetDetails($id)
    {
        $datasetDetails = $this->makeRequest("package_show?id=$id");

        if (!isset($datasetDetails['error'])) {
            return [
                'id' => $datasetDetails['id'] ?? 'Unknown ID',
                'name' => $datasetDetails['name'] ?? 'Unknown Name',
                'title' => $datasetDetails['title'] ?? 'No Title',
                'notes' => $datasetDetails['notes'] ?? 'No Notes Available',
                'url' => $datasetDetails['url'] ?? 'No URL',
                'num_resources' => $datasetDetails['num_resources'] ?? 0,
                'metadata_created' => $datasetDetails['metadata_created'] ?? 'Unknown Date',
                'metadata_modified' => $datasetDetails['metadata_modified'] ?? 'Unknown Date',
                'resources' => $datasetDetails['resources'] ?? [],
                'tags' => array_map(function ($tag) {
                    return ['name' => $tag['name'] ?? 'Unknown', 'display_name' => $tag['display_name'] ?? 'Unknown'];
                }, $datasetDetails['tags'] ?? [])
            ];
        } else {
            return $datasetDetails;
        }
    }
    /**
     * @OA\Get(
     *     path="/ckan-datasets-by-tag/{tag}",
     *     summary="Obtiene conjuntos de datos por etiqueta",
     *     description="Recupera conjuntos de datos asociados con una etiqueta específica, con opciones de paginación.",
     *     @OA\Parameter(
     *         name="tag",
     *         in="path",
     *         required=true,
     *         description="Etiqueta para filtrar los conjuntos de datos",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Índice de inicio para la paginación de los conjuntos de datos",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="rows",
     *         in="query",
     *         required=false,
     *         description="Número de conjuntos de datos a devolver en la respuesta",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de conjuntos de datos filtrados por etiqueta",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="count",
     *                 type="integer",
     *                 description="Número total de conjuntos de datos encontrados"
     *             ),
     *             @OA\Property(
     *                 property="datasets",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Dataset"),
     *                 description="Conjuntos de datos que coinciden con la etiqueta especificada"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud"
     *     )
     * )
     */
    public function fetchDatasetsByTag($tag, $start = 0, $rows = 100)
    {
        $searchQuery = http_build_query([
            'fq' => 'tags:' . urlencode($tag),
            'start' => $start,
            'rows' => $rows
        ]);

        $searchResults = $this->makeRequest("package_search?$searchQuery");
        if (!isset($searchResults['error'])) {
            return [
                'count' => $searchResults['count'],
                'datasets' => $this->processCkanResults($searchResults['results'])
            ];
        } else {
            return $searchResults;
        }
    }
    /**
     * @OA\Get(
     *     path="/ckan-datasets/most_downloaded/{rows}",
     *     summary="Obtiene los conjuntos de datos más descargados",
     *     description="Recupera una lista de los conjuntos de datos más descargados, limitada a un número específico de filas.",
     *     @OA\Parameter(
     *         name="rows",
     *         in="path",
     *         required=true,
     *         description="Número de filas o conjuntos de datos a devolver",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de los conjuntos de datos más descargados",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="count",
     *                 type="integer",
     *                 description="Número total de conjuntos de datos más descargados disponibles"
     *             ),
     *             @OA\Property(
     *                 property="datasets",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Dataset"),
     *                 description="Conjuntos de datos más descargados"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud"
     *     )
     * )
     */
    public function getMostDownloaded($rows)
    {
        $sort = 'views_total+desc';
        $searchResults = $this->makeRequest("package_search?sort=$sort&rows=$rows");
        if (!isset($searchResults['error'])) {
            return [
                'count' => $searchResults['count'],
                'datasets' => $this->processCkanResults($searchResults['results'])
            ];
        } else {
            return $searchResults;
        }
    }
    /**
     * @OA\Get(
     *     path="/ckan-datasets/latest/{rows}",
     *     summary="Obtiene los conjuntos de datos más recientes",
     *     description="Recupera una lista de los conjuntos de datos más recientemente actualizados, limitada a un número específico de filas.",
     *     @OA\Parameter(
     *         name="rows",
     *         in="path",
     *         required=true,
     *         description="Número de filas o conjuntos de datos a devolver",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de los conjuntos de datos más recientes",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="count",
     *                 type="integer",
     *                 description="Número total de conjuntos de datos más recientes disponibles"
     *             ),
     *             @OA\Property(
     *                 property="datasets",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Dataset"),
     *                 description="Conjuntos de datos más recientes"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud"
     *     )
     * )
     */
    public function getLatest($rows)
    {
        $sort = 'metadata_modified+desc';
        $searchResults = $this->makeRequest("package_search?sort=$sort&rows=$rows");
        if (!isset($searchResults['error'])) {
            return [
                'count' => $searchResults['count'],
                'datasets' => $this->processCkanResults($searchResults['results'])
            ];
        } else {
            return $searchResults;
        }
    }
    /**
     * @OA\Get(
     *     path="/ckan-datasets/most_searched/{rows}",
     *     summary="Obtiene los conjuntos de datos más buscados",
     *     description="Recupera una lista de los conjuntos de datos más buscados, limitada a un número específico de filas.",
     *     @OA\Parameter(
     *         name="rows",
     *         in="path",
     *         required=true,
     *         description="Número de filas o conjuntos de datos a devolver",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de los conjuntos de datos más buscados",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="datasets",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Dataset"),
     *                 description="Conjuntos de datos más buscados"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud"
     *     )
     * )
     */
    public function getMostSearched($rows)
    {
        $searchResults = $this->elasticsearch->search([
            'index' => 'search_datasets_id',
            'size' => 0,
            'body' => [
                'aggs' => [
                    'popular_datasets' => [
                        'terms' => ['field' => 'data.dataset_id.keyword', 'size' => $rows]
                    ]
                ]
            ]
        ]);

        $popularDatasetIds = [];
        if (isset($searchResults['aggregations'])) {
            $popularDatasetIds = array_column($searchResults['aggregations']['popular_datasets']['buckets'], 'key');
        }

        $datasets = [];
        foreach ($popularDatasetIds as $id) {
            $datasetDetails = $this->fetchDatasetDetails($id);
            if (!isset($datasetDetails['error'])) {
                // Procesa cada conjunto de datos a través de processCkanResults
                $processedDetails = $this->processCkanResults([$datasetDetails]);
                $datasets = array_merge($datasets, $processedDetails);
            }
        }

        return ['datasets' => $datasets];
    }
    /**
     * @OA\Get(
     *     path="/ckan-datasets/search",
     *     summary="Busca en los conjuntos de datos",
     *     description="Realiza una búsqueda en los conjuntos de datos basándose en un término de búsqueda y devuelve una lista de resultados.",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=false,
     *         description="Término de búsqueda para los conjuntos de datos",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resultados de la búsqueda de conjuntos de datos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="count",
     *                 type="integer",
     *                 description="Número total de resultados de la búsqueda"
     *             ),
     *             @OA\Property(
     *                 property="datasets",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Dataset"),
     *                 description="Conjuntos de datos que coinciden con el término de búsqueda"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud"
     *     )
     * )
     */
    public function searchDatasets(Request $request)
    {
        $searchTerm = $request->input('query', '');
        $searchResults = $this->makeRequest("package_search?q=" . urlencode($searchTerm));

        if (!isset($searchResults['error'])) {
            $datasetIds = array_column($searchResults['results'], 'id');

            foreach ($datasetIds as $id) {
                $this->trackEvent('dataset_searched', ['dataset_id' => $id]);
            }

            return [
                'count' => $searchResults['count'],
                'datasets' => $this->processCkanResults($searchResults['results'])
            ];
        } else {
            return $searchResults;
        }
    }

    private function makeRequest($path, $key = null)
    {
        try {
            $response = $this->client->request('GET', "{$this->baseUrl}/$path");
            $result = json_decode($response->getBody(), true);

            if ($result['success']) {
                return is_null($key) ? $result['result'] : array_column($result['result']['results'], $key);
            } else {
                return ['error' => 'Error getting data from CKAN'];
            }
        } catch (\Exception $e) {
            return ['error' => 'Error when making request: ' . $e->getMessage()];
        }
    }

    private function processCkanResults($results)
    {
        return array_map(function ($dataset) {
            $resourceFormats = isset($dataset['resources']) ? array_map(function ($resource) {
                return isset($resource['format']) ? $resource['format'] : 'Unknown Format';
            }, $dataset['resources']) : [];

            $apiDetailUrl = url("/ckan-datasets/details/" . (isset($dataset['id']) ? $dataset['id'] : 'unknown_id'));

            return [
                'id' => isset($dataset['id']) ? $dataset['id'] : 'Unknown ID',
                'name' => isset($dataset['name']) ? $dataset['name'] : 'Unknown Name',
                'notes' => isset($dataset['notes']) ? $dataset['notes'] : 'No Notes Available',
                'resource_formats' => array_unique($resourceFormats),
                'api_detail_url' => $apiDetailUrl
            ];
        }, $results);
    }

    protected function trackEvent($eventType, $data)
    {
        $esData = [
            'index' => 'search_datasets_id',
            'body'  => [
                'event_type' => $eventType,
                'data' => $data,
                'timestamp' => date('c')
            ]
        ];
        try {
            $response = $this->elasticsearch->index($esData);
            Log::info('Datos enviados a Elasticsearch: ' . json_encode($esData));
            Log::info('Respuesta de Elasticsearch: ' . json_encode($response));
        } catch (\Exception $e) {
            Log::error("Error al enviar datos a Elasticsearch: " . $e->getMessage());
        }
    }
}

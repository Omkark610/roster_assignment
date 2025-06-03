<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;

/*======================================
Part 2: Sample code
========================================
Overview of the Solution:
1) Extract Embeddings: Use OpenAI’s embedding API (e.g., text-embedding-ada-002) to generate vector representations of each talent's profile.
2) Store Vectors: Save vectors in a talent_embeddings table.
3) Search & Rank: When a query is issued, embed the query and compute cosine similarity with stored vectors.
4) Respond: Return the top-matching talent IDs or profiles.
========================================

========================================
Key Features Implemented in the SemanticSearchService:
1) getEmbedding(): Calls OpenAI to generate embedding.
2) saveTalentEmbedding(): Stores it in DB.
3) searchSimilarTalent(): Accepts a query, compares it to all talent embeddings, and returns sorted results.
4) cosineSimilarity(): Manually calculates similarity.
========================================
*/

class SemanticSearchService
{
    protected string $embeddingApiUrl = 'https://api.openai.com/v1/embeddings';
    protected string $embeddingModel = 'text-embedding-ada-002';

    //getEmbedding(): Calls OpenAI to generate embedding.
    public function getEmbedding(string $text): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->post($this->embeddingApiUrl, [
            'input' => $text,
            'model' => $this->embeddingModel,
        ]);

        if ($response->successful()) {
            return $response->json('data')[0]['embedding'];
        }

        return null;
    }

    public function fetchTalentProfileText(string $url): ?string
    {
        $html = Http::get($url)->body();
        $crawler = new Crawler($html);

        $text = $crawler->filter('body')->text();

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function generateAndStoreEmbeddingFromUrl(int $talentId, string $url): bool
    {
        $text = $this->fetchTalentProfileText($url);

        if (!$text) {
            return false;
        }

        $embedding = $this->getEmbedding($text);

        if (!$embedding) {
            return false;
        }

        $this->saveTalentEmbedding($talentId, $embedding);

        return true;
    }

    //saveTalentEmbedding(): Stores it in DB.
    public function saveTalentEmbedding(int $talentId, array $embedding): void
    {
        DB::table('talent_embeddings')->updateOrInsert(
            ['talent_id' => $talentId],
            ['vector' => json_encode($embedding)]
        );
    }

    // searchSimilarTalent(): Accepts a query, compares it to all talent embeddings, and returns sorted results.
    public function searchSimilarTalent(string $query, int $limit = 10): array
    {
        $queryVector = $this->getEmbedding($query);

        if (!$queryVector) {
            return [];
        }

        $talents = DB::table('talent_embeddings')->get();

        $ranked = $talents->map(function ($row) use ($queryVector) {
            $score = $this->cosineSimilarity(json_decode($row->vector), $queryVector);
            return [
                'talent_id' => $row->talent_id,
                'score' => $score
            ];
        })->sortByDesc('score')->take($limit);

        return $ranked->pluck('talent_id')->all();
    }

    // cosineSimilarity(): Manually calculates similarity.
    protected function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = array_sum(array_map(fn($x, $y) => $x * $y, $a, $b));
        $magnitudeA = sqrt(array_sum(array_map(fn($x) => $x ** 2, $a)));
        $magnitudeB = sqrt(array_sum(array_map(fn($x) => $x ** 2, $b)));

        return $magnitudeA && $magnitudeB ? $dotProduct / ($magnitudeA * $magnitudeB) : 0.0;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;

class PortfolioController extends Controller {

    public function ingest(Request $request) {
        $request->validate([
            'url' => 'required|url',
            'username' => 'nullable|string'
        ]);
        
        $html = Http::get($request->input('url'))->body();
        $username = $request->input('username') ?? Str::random(8);

        $crawler = new Crawler($html);
        
        // Extract name from title
        $name = $crawler->filter('title')->count() ? $crawler->filter('title')->text() : null;
        $name = ucwords(strtolower(str_ireplace('PORTFOLIO', '', $name)));
        
        // Extract email using crawler
        $emailText = collect($crawler->filter('body')->text())->first(fn($t) => str_contains($t, '@'));
        // Fix email extraction using regex instead of collect on whole text.
        preg_match('/[\w._%+-]+@[\w.-]+\.[a-zA-Z]{2,}/', $emailText, $matches);
        $email = $matches[0] ?? null;
        
        // Extract phone using crawler
        $phoneText = collect($crawler->filter('body')->text())->first(fn($t) => preg_match('/\d{10}/', $t));
        preg_match('/\+91\d{10}/', $phoneText, $matches);
        $phone = $matches[0] ?? null;
        
        $talent = Talent::create(compact('username', 'name', 'email', 'phone'));
        
        // Crawl clients names
        $testimonialsSection = $crawler->filterXPath("//*[contains(translate(text(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'clients testimonial')]")->first();
        $clientNames = [];
        // dd($testimonialsSection->count());
        if ($testimonialsSection->count()) {
            $parentNode = $testimonialsSection->getNode(0)->parentNode;
            $sectionText = $parentNode ? $parentNode->textContent : '';
            preg_match_all('/[A-Z][a-z]+\\s[A-Z][a-z]+/', $sectionText, $matches);
            $clientNames = $matches[0];
        }
        
        // Bulk insert into clients table
        $clientData = collect($clientNames)->map(fn($name) => [
            'name' => $name,
            'talent_id' => $talent->id, 
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        \DB::table('clients')->insert($clientData);
        
        // Extract all YouTube links from the raw HTML
        $html = $crawler->html();
        preg_match_all('/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)[\w\-]+/', $html, $matches);
        $youtubeLinks = $matches[0];
        
        // Save YouTube links to videos table without client_id
        $videoData = collect($youtubeLinks)->map(fn($url) => [
            'talent_id' => $talent->id, 
            'title' => 'YouTube Video',
            'url' => $url,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        \DB::table('videos')->insert($videoData);

        return response()->json(['message' => 'Profile ingested', 'username' => $username]);
    }

    public function show($username)
    {
        $talent = Talent::with('clients', 'videos')->where('username', $username)->first();

        if (!$talent) {
            return response()->json([
                'error' => 'Talent profile not found.'
            ], 404);
        }

        return response()->json([
            'username' => $talent->username,
            'basic_info' => $talent->only(['name', 'email', 'phone']),
            'clients' => $talent->clients->map(fn($client) => [
                'id' => $client->id,
                'name' => $client->name
            ]),
            'videos' => $talent->videos->map(fn($video) => [
                'id' => $video->id,
                'title' => $video->title,
                'url' => $video->url
            ])
        ]);
    }

    public function update(Request $request, $username) {
        $talent = Talent::where('username', $username)->firstOrFail();
        $talent->update($request->only(['name', 'email', 'phone']));
        
        //Update or create clients
        if ($request->has('clients')) {
            foreach ($request->clients as $clientData) {
                $talent->clients()->updateOrCreate(
                    ['id' => $clientData['id'] ?? null],
                    ['name' => $clientData['name']]
                );
            }
        }

        //Update or create videos
        if ($request->has('videos')) {
            foreach ($request->videos as $videoData) {
                $talent->videos()->updateOrCreate(
                    ['id' => $videoData['id'] ?? null],
                    ['title' => $videoData['title'], 'url' => $videoData['url']]
                );
            }
        }

        return response()->json(['message' => 'Profile updated']);
    }

    public function destroy($username) {
        $talent = Talent::where('username', $username)->firstOrFail();
        $talent->delete();
        return response()->json(['message' => 'Profile deleted']);
    }
}
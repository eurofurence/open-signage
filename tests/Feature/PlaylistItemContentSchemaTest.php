<?php

namespace Tests\Feature;

use App\Filament\Resources\Playlists\Pages\EditPlaylist;
use App\Filament\Resources\Playlists\RelationManagers\PlaylistItemsRelationManager;
use App\Models\Layout;
use App\Models\Page;
use App\Models\Playlist;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlaylistItemContentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_a_page_creates_a_state_key_for_every_content_property()
    {
        $this->actingAs(User::factory()->create(['password' => bcrypt('password')]));

        $project = Project::factory()->create(['path' => 'System']);
        $playlist = Playlist::factory()->create(['project_id' => $project->id]);
        $layout = Layout::create([
            'project_id' => $project->id,
            'name' => 'None',
            'component' => 'None',
        ]);
        $page = Page::create([
            'project_id' => $project->id,
            'name' => 'Announcement',
            'component' => 'Announcement',
            'schema' => [
                ['name' => 'Header', 'property' => 'title', 'type' => 'TextInput'],
                ['name' => 'Text', 'property' => 'text', 'type' => 'RichEditor'],
                ['name' => 'Text Size', 'property' => 'textSize', 'type' => 'TextInput'],
            ],
        ]);

        $component = Livewire::test(PlaylistItemsRelationManager::class, [
            'ownerRecord' => $playlist,
            'pageClass' => EditPlaylist::class,
        ])
            ->mountAction(TestAction::make('create')->table())
            ->set('mountedActions.0.data.page_id', $page->id)
            ->set('mountedActions.0.data.layout_id', $layout->id);

        $content = $component->get('mountedActions.0.data.content');

        foreach (['title', 'text', 'textSize'] as $property) {
            $this->assertArrayHasKey(
                $property,
                $content,
                "Missing state key for [{$property}]: fields bound with Alpine entangle (RichEditor and friends) silently discard input when their state path does not exist.",
            );
        }
    }
}

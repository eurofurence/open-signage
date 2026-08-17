<?php

namespace App\Filament\Resources\ScheduleEntries;

use App\Filament\Resources\ScheduleEntries\Pages\CreateScheduleEntry;
use App\Filament\Resources\ScheduleEntries\Pages\EditScheduleEntry;
use App\Filament\Resources\ScheduleEntries\Pages\ListScheduleEntries;
use App\Models\Playlist;
use App\Models\Project;
use App\Models\ScheduleEntry;
use App\Models\Screen;
use BackedEnum;
use Carbon\CarbonInterval;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ScheduleEntryResource extends Resource
{
    protected static ?string $model = ScheduleEntry::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-m-table-cells';

    protected static ?string $slug = 'schedule-entries';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()->columnSpan(2)->columns()->schema([
                    Section::make('Meta')->schema([
                        TextInput::make('title')
                            ->required(),
                        Textarea::make('description'),

                        Select::make('room_id')
                            ->relationship('room', 'name', modifyQueryUsing: fn (Builder $query, Get $get) => $query
                                ->when($get('project_id'), fn (Builder $query, $projectId) => $query->where('project_id', $projectId)))
                            ->required()
                            ->createOptionForm(fn (Schema $schema, Get $get) => $schema->components([
                                TextInput::make('name')
                                    ->required(),
                                Select::make('project_id')
                                    ->relationship('project', 'name')
                                    ->default(fn () => $get('project_id'))
                                    ->required()
                                    ->disabled(),
                            ]))
                            ->editOptionForm(fn (Schema $schema) => $schema->components([
                                TextInput::make('name')
                                    ->required(),
                            ])),

                        Select::make('schedule_organizer_id')
                            ->relationship('scheduleOrganizer', 'name')
                            ->createOptionForm(fn (Schema $schema) => $schema->components([
                                TextInput::make('name')
                                    ->required(),
                            ]))
                            ->editOptionForm(fn (Schema $schema) => $schema->components([
                                TextInput::make('name')
                                    ->required(),
                            ])),

                        Select::make('schedule_type_id')
                            ->relationship('scheduleType', 'name')
                            ->createOptionForm(fn (Schema $schema) => $schema->components([
                                TextInput::make('name')
                                    ->required(),
                                ColorPicker::make('color')
                                    ->required(),
                            ]))
                            ->editOptionForm(fn (Schema $schema) => $schema->components([
                                TextInput::make('name')
                                    ->required(),
                                ColorPicker::make('color')
                                    ->required(),
                            ]))->helperText('Color is used for the background in the timetable.'),

                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->default(fn () => Project::where('path', config('app.default_project'))->first()?->id)
                            ->live(),

                        TextInput::make('external_id')
                            ->label('External ID')
                            ->helperText('Pretalx ID. Do not touch unless you know what you are doing!')
                            ->string(),
                    ])->columnSpan(1),
                    Group::make([
                        Section::make('Event Time')->schema([
                            DateTimePicker::make('starts_at')
                                ->native(true)
                                ->reactive()
                                ->maxDate(fn (Get $get) => $get('ends_at'))
                                ->required()
                                ->label('Starts Date'),

                            DateTimePicker::make('ends_at')
                                ->native(true)
                                ->reactive()
                                ->minDate(fn (Get $get) => $get('starts_at'))
                                ->required()
                                ->label('Ends Date'),
                        ])->columnSpan(1),
                        Section::make('Automation')->schema([
                            Repeater::make('automation')->schema([

                                Select::make('screens')
                                    ->options(
                                        Screen::where('provisioned', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                    )
                                    ->multiple()
                                    ->required(),

                                Select::make('type')
                                    ->label('Type of Automation')
                                    ->options([
                                        'on_start' => 'On Start',
                                        'on_end' => 'On End',
                                        'on_start_with_delay' => 'On Start (with delay)',
                                        'on_end_with_delay' => 'On End (with delay)',
                                    ])
                                    ->required(),

                                Select::make('playlist')
                                    ->label('Set Playlist')
                                    ->options(
                                        Playlist::normal()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                    )
                                    ->required(),

                                Checkbox::make('has_run')
                                    ->label('Automation has already run')
                                    ->helperText('If this is checked the automation will not run again.'),
                            ]),
                        ]),
                    ]),
                ]),
                Group::make()->columnSpan(1)->columns(1)->schema([
                    Section::make('Flags')->schema([
                        CheckboxList::make('flags')
                            ->options([
                                'moved' => 'Moved',
                                'cancelled' => 'Cancelled',
                                'after_dark' => 'After Dark',
                            ]),

                        TextInput::make('delay')
                            ->default(0)
                            ->required()
                            ->numeric()
                            ->suffix('minutes')
                            ->hintAction(Action::make('realizeDelay')
                                ->label('Realize Delay')
                                ->requiresConfirmation()
                                ->visible(function (?ScheduleEntry $entry) {
                                    return $entry?->delay > 0;
                                })
                                ->action(function (ScheduleEntry $entry, Set $set) {
                                    $delay = CarbonInterval::make($entry->delay, 'minutes');
                                    $entry->starts_at = $entry->starts_at->add($delay);
                                    $entry->ends_at = $entry->ends_at->add($delay);
                                    $entry->delay = 0;
                                    $entry->save();

                                    $set('starts_at', $entry->starts_at);
                                    $set('ends_at', $entry->ends_at);
                                    $set('delay', $entry->delay);
                                })),

                        Textarea::make('message')
                            ->helperText('Use in combination with delay (will be displayed as delay reason) or cancelled (as cancel reason).'),

                    ])->columnSpan(1),

                    TextEntry::make('created_at')
                        ->label('Created Date')
                        ->state(fn (
                            ?ScheduleEntry $record
                        ): string => $record?->created_at?->diffForHumans() ?? '-'),

                    TextEntry::make('updated_at')
                        ->label('Last Modified Date')
                        ->state(fn (
                            ?ScheduleEntry $record
                        ): string => $record?->updated_at?->diffForHumans() ?? '-'),
                ]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('room.name')
                    ->sortable()->searchable(),

                TextColumn::make('external_id')
                    ->label('External ID')
                    ->sortable()->searchable(),

                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->sortable()
                    ->dateTime(),

                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->sortable()
                    ->dateTime(),

                TextColumn::make('delay')
                    ->label('Delay')
                    ->sortable(),

            ])->filters([
                SelectFilter::make('room_id')->multiple()->preload()->label('Rooms')->relationship('room', 'name'),
                SelectFilter::make('schedule_type_id')->multiple()->preload()->label('Schedule Types')->relationship('scheduleType',
                    'name'),
                SelectFilter::make('project_id')->multiple()->preload()->label('Projects')->relationship('project', 'name')
                    ->default(Project::where('path', config('app.default_project'))->firstOrFail()->id),
            ])->recordActions([
                EditAction::make(),
                ReplicateAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduleEntries::route('/'),
            'create' => CreateScheduleEntry::route('/create'),
            'edit' => EditScheduleEntry::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title'];
    }
}

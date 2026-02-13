<?php

namespace App\Filament\Pages;

use App\Services\SearchConsoleService;
use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class SearchConsoleSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Google Search Console';

    protected static ?string $title = 'Google Search Console Settings';

    protected static string $view = 'filament.pages.search-console-settings';

    public ?array $data = [];

    public bool $connectionTested = false;
    public ?string $connectionStatus = null;
    public array $availableSites = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'gsc_service_account_json' => $settings->get('gsc_service_account_json'),
            'gsc_site_url' => $settings->get('gsc_site_url'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setup Instructions')
                    ->description('Follow these steps to connect Google Search Console.')
                    ->schema([
                        Forms\Components\Placeholder::make('instructions')
                            ->label('')
                            ->content(new HtmlString('
                                <ol class="list-decimal list-inside space-y-2 text-sm">
                                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank" class="text-primary-600 underline font-medium">Google Cloud Console</a></li>
                                    <li>Create a new project (or select an existing one)</li>
                                    <li>Enable the <strong>Google Search Console API</strong>: Go to APIs &amp; Services → Library → search "Google Search Console API" → Enable</li>
                                    <li>Create a Service Account: Go to APIs &amp; Services → Credentials → Create Credentials → Service Account</li>
                                    <li>Give it a name (e.g. "AD Perfumes GSC") and click Create</li>
                                    <li>Skip the optional permissions steps, click Done</li>
                                    <li>Click on the created service account → Keys tab → Add Key → Create New Key → JSON → Create</li>
                                    <li>A JSON file will download — upload it below</li>
                                    <li>Copy the service account email (looks like <code class="bg-gray-100 px-1 rounded text-xs">name@project.iam.gserviceaccount.com</code>)</li>
                                    <li>In <a href="https://search.google.com/search-console" target="_blank" class="text-primary-600 underline font-medium">Google Search Console</a>, go to Settings → Users and permissions → Add User</li>
                                    <li>Paste the service account email and set permission to <strong>Full</strong> (or at least Read)</li>
                                </ol>
                            ')),
                    ])->collapsible(),

                Forms\Components\Section::make('Credentials')
                    ->schema([
                        Forms\Components\FileUpload::make('gsc_service_account_json')
                            ->label('Service Account JSON Key File')
                            ->disk('local')
                            ->directory('gsc')
                            ->acceptedFileTypes(['application/json'])
                            ->maxSize(50)
                            ->helperText('Upload the JSON key file downloaded from Google Cloud Console. Stored securely on server (not publicly accessible).'),

                        Forms\Components\TextInput::make('gsc_site_url')
                            ->label('Site URL (Property)')
                            ->placeholder('https://www.adperfumes.com')
                            ->helperText('The exact URL property as it appears in Google Search Console (e.g. https://www.adperfumes.com or sc-domain:adperfumes.com)')
                            ->maxLength(255),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        foreach ($data as $key => $value) {
            $settings->set($key, $value);
        }

        Notification::make()
            ->title('Search Console settings saved successfully')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $service = app(SearchConsoleService::class);

        if (!$service->isConfigured()) {
            Notification::make()
                ->title('Please upload credentials and save first')
                ->danger()
                ->send();

            return;
        }

        $result = $service->testConnection();

        if ($result['success']) {
            $this->connectionTested = true;
            $this->connectionStatus = 'success';
            $this->availableSites = $result['sites'];

            Notification::make()
                ->title('Connection successful!')
                ->body('Found ' . count($result['sites']) . ' site(s) accessible.')
                ->success()
                ->send();
        } else {
            $this->connectionTested = true;
            $this->connectionStatus = 'error';

            Notification::make()
                ->title('Connection failed')
                ->body($result['error'])
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}

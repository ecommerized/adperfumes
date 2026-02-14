<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialMediaPostResource\Pages;
use App\Jobs\PublishSocialMediaPostJob;
use App\Models\Product;
use App\Models\SocialMediaPost;
use App\Services\SocialMediaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SocialMediaPostResource extends Resource
{
    protected static ?string $model = SocialMediaPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Social Posts';

    protected static ?string $modelLabel = 'Social Media Post';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Post Type & Content')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'product_promo' => 'Product Promotion',
                                'offer' => 'Offer / Discount',
                                'brand_story' => 'Brand Story',
                                'custom' => 'Custom',
                            ])
                            ->default('product_promo')
                            ->required()
                            ->reactive()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a product (optional)')
                            ->visible(fn ($get) => in_array($get('type'), ['product_promo', 'custom']))
                            ->reactive()
                            ->helperText('Select a product to promote. Its image can be used for the post.'),

                        Forms\Components\Select::make('discount_id')
                            ->label('Discount Code')
                            ->relationship('discount', 'code')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a discount (optional)')
                            ->visible(fn ($get) => in_array($get('type'), ['offer', 'custom']))
                            ->helperText('Link this post to a discount code.'),

                        Forms\Components\Toggle::make('generate_ai_image')
                            ->label('Generate AI Image')
                            ->helperText('If product selected: Creates branded design using actual product image. Otherwise: Uses DALL-E 3 ($0.04 per image)')
                            ->default(false)
                            ->reactive()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('image_size_type')
                            ->label('Image Size')
                            ->options([
                                'post' => 'Post (1080x1080 - Square)',
                                'story' => 'Story (1080x1920 - Vertical)',
                            ])
                            ->default('post')
                            ->visible(fn ($get) => $get('generate_ai_image'))
                            ->helperText('Choose the format for your social media image')
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('generate_caption')
                                ->label('Generate Caption & Image with AI')
                                ->icon('heroicon-o-sparkles')
                                ->color('warning')
                                ->action(function ($get, $set, $livewire) {
                                    $type = $get('type') ?? 'custom';
                                    $generateImage = $get('generate_ai_image') ?? false;
                                    $sizeType = $get('image_size_type') ?? 'post';
                                    $context = ['size_type' => $sizeType];

                                    $productId = $get('product_id');
                                    if ($productId) {
                                        $product = Product::with(['brand', 'categories', 'topNotes', 'middleNotes', 'baseNotes'])->find($productId);
                                        if ($product) {
                                            $context = array_merge($context, [
                                                'product_name' => $product->name,
                                                'product_description' => mb_substr($product->description ?? '', 0, 500),
                                                'brand_name' => $product->brand?->name,
                                                'price' => $product->price,
                                                'original_price' => $product->original_price,
                                                'on_sale' => $product->on_sale,
                                                'categories' => $product->categories->pluck('name')->toArray(),
                                                'notes' => [
                                                    'top' => $product->topNotes->pluck('name')->toArray(),
                                                    'middle' => $product->middleNotes->pluck('name')->toArray(),
                                                    'base' => $product->baseNotes->pluck('name')->toArray(),
                                                ],
                                                'product_url' => url("/products/{$product->slug}"),
                                                'product_image_path' => $product->image, // For actual product image in design
                                            ]);
                                        }
                                    }

                                    $discountId = $get('discount_id');
                                    if ($discountId) {
                                        $discount = \App\Models\Discount::find($discountId);
                                        if ($discount) {
                                            $context = array_merge($context, [
                                                'discount_code' => $discount->code,
                                                'discount_value' => $discount->formatted_value,
                                                'discount_description' => $discount->description,
                                            ]);
                                        }
                                    }

                                    $service = app(SocialMediaService::class);
                                    $result = $service->generateCaption($type, $context, $generateImage);

                                    if ($result) {
                                        $set('caption', $result['caption']);
                                        $set('hashtags', $result['hashtags']);

                                        // Store generated image path in a hidden field to avoid FileUpload foreach error
                                        if (isset($result['image_path'])) {
                                            $set('generated_image_path', $result['image_path']);
                                        }

                                        Notification::make()
                                            ->title($generateImage ? 'Caption & Image generated!' : 'Caption generated!')
                                            ->body($generateImage ? "Image size: {$sizeType}" : '')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Failed to generate content')
                                            ->body('Check your API keys in Social Media Settings.')
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull(),

                        Forms\Components\Textarea::make('caption')
                            ->required()
                            ->rows(6)
                            ->maxLength(5000)
                            ->columnSpanFull()
                            ->helperText('The main text of your Facebook post. Use the AI button above to auto-generate.'),

                        Forms\Components\Textarea::make('hashtags')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->helperText('Hashtags will be appended after the caption.'),
                    ])->columns(2),

                Forms\Components\Section::make('Image')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Post Image')
                            ->image()
                            ->disk('public')
                            ->directory('social-posts')
                            ->maxSize(5120)
                            ->helperText('AI will use actual product image with branded design, or generate with DALL-E 3, or upload custom. Recommended: 1024x1024px. Max 5MB.')
                            ->imagePreviewHeight('300')
                            ->live(false) // Disable reactive updates to avoid foreach error
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('generated_image_path')
                            ->dehydrateStateUsing(fn ($state) => $state),

                        Forms\Components\Placeholder::make('generated_image_preview')
                            ->label('Generated Image Preview')
                            ->reactive()
                            ->content(function ($get) {
                                $imagePath = $get('generated_image_path') ?: (is_array($get('image_path')) ? ($get('image_path')[0] ?? '') : $get('image_path'));

                                if (empty($imagePath)) {
                                    return new \Illuminate\Support\HtmlString('<p style="color: #9ca3af; font-size: 13px;">No image generated yet. Click "Generate Caption & Image with AI" above.</p>');
                                }

                                $imageUrl = \Storage::disk('public')->url($imagePath);
                                $isProduct = str_contains($imagePath, 'social-product-');
                                $isAI = str_contains($imagePath, 'social-ai-');
                                $isStory = str_contains($imagePath, '-story-');
                                $isPost = str_contains($imagePath, '-post-');

                                $sizeLabel = $isStory ? ' (Story 1080x1920)' : ($isPost ? ' (Post 1080x1080)' : '');

                                $badge = '';
                                if ($isProduct) {
                                    $badge = '<div style="margin-bottom: 12px; padding: 10px; background: #eff6ff; border: 1px solid #60a5fa; border-radius: 6px; color: #1e40af; font-size: 13px;">
                                        <strong>✓ Product Image Design' . $sizeLabel . '</strong> - Using actual product photo with branded background
                                    </div>';
                                } elseif ($isAI) {
                                    $badge = '<div style="margin-bottom: 12px; padding: 10px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; color: #166534; font-size: 13px;">
                                        <strong>✓ AI Generated' . $sizeLabel . '</strong> - DALL-E 3 design with logo overlay
                                    </div>';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    $badge . '<img src="' . e($imageUrl) . '?t=' . time() . '" style="max-width: 100%; max-height: 500px; border-radius: 8px; border: 2px solid #e5e7eb; display: block; margin: 0 auto;" />'
                                );
                            })
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('regenerate_image')
                                ->label('Regenerate AI Image')
                                ->icon('heroicon-o-arrow-path')
                                ->color('warning')
                                ->visible(fn ($get) => !empty($get('generate_ai_image')))
                                ->action(function ($get, $set) {
                                    $type = $get('type') ?? 'custom';
                                    $context = [];

                                    $productId = $get('product_id');
                                    if ($productId) {
                                        $product = Product::with(['brand', 'categories'])->find($productId);
                                        if ($product) {
                                            $context = [
                                                'product_name' => $product->name,
                                                'brand_name' => $product->brand?->name,
                                                'price' => $product->price,
                                                'product_image_path' => $product->image, // For actual product image
                                            ];
                                        }
                                    }

                                    $discountId = $get('discount_id');
                                    if ($discountId) {
                                        $discount = \App\Models\Discount::find($discountId);
                                        if ($discount) {
                                            $context['discount_code'] = $discount->code;
                                            $context['discount_value'] = $discount->formatted_value;
                                        }
                                    }

                                    $service = app(SocialMediaService::class);
                                    $imagePath = $service->generateImage($type, $context);

                                    if ($imagePath) {
                                        // For saved files on disk, set as string path
                                        $set('image_path', $imagePath);
                                        Notification::make()
                                            ->title('New image generated!')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Image generation failed')
                                            ->body('Check your OpenAI API key in settings.')
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ])
                        ->visible(fn ($get) => !empty($get('generate_ai_image')))
                        ->columnSpanFull(),

                        Forms\Components\Placeholder::make('product_image_preview')
                            ->label('Product Image (will be used if no AI/custom image)')
                            ->content(function ($get) {
                                $productId = $get('product_id');
                                if ($productId) {
                                    $product = Product::find($productId);
                                    if ($product && $product->image) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<img src="' . e(\Storage::url($product->image)) . '" style="max-width:200px; border-radius:8px; border: 2px solid #e5e7eb;" />'
                                        );
                                    }
                                }
                                return 'No product selected or product has no image.';
                            })
                            ->visible(fn ($get) => !empty($get('product_id')) && empty($get('image_path')) && empty($get('generate_ai_image')))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Scheduling & Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'scheduled' => 'Scheduled',
                            ])
                            ->default('draft')
                            ->required()
                            ->reactive(),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Schedule For')
                            ->visible(fn ($get) => $get('status') === 'scheduled')
                            ->required(fn ($get) => $get('status') === 'scheduled')
                            ->minDate(now())
                            ->helperText('Post will be auto-published to Facebook at this time (UAE timezone).'),

                        Forms\Components\Placeholder::make('source_display')
                            ->label('Source')
                            ->content(fn ($record) => $record?->source === 'auto_pilot' ? 'Auto-Pilot' : 'Manual')
                            ->visibleOn('edit'),
                    ])->columns(2),

                Forms\Components\Section::make('Publishing Info')
                    ->schema([
                        Forms\Components\Placeholder::make('published_at_display')
                            ->label('Published At')
                            ->content(fn ($record) => $record?->published_at?->format('M d, Y H:i') ?? 'Not published yet'),

                        Forms\Components\Placeholder::make('facebook_post_id_display')
                            ->label('Facebook Post ID')
                            ->content(fn ($record) => $record?->facebook_post_id ?? 'N/A'),

                        Forms\Components\Placeholder::make('error_display')
                            ->label('Error Message')
                            ->content(fn ($record) => $record?->error_message ?? 'None')
                            ->visible(fn ($record) => $record?->status === 'failed'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'product_promo' => 'Product',
                        'offer' => 'Offer',
                        'brand_story' => 'Brand Story',
                        'custom' => 'Custom',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state) => match ($state) {
                        'product_promo' => 'primary',
                        'offer' => 'success',
                        'brand_story' => 'warning',
                        'custom' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('caption')
                    ->limit(60)
                    ->searchable()
                    ->tooltip(fn ($record) => Str::limit($record->caption, 200)),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(25)
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'posting' => 'info',
                        'published' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'auto_pilot' ? 'Auto' : 'Manual')
                    ->color(fn (string $state) => $state === 'auto_pilot' ? 'info' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'product_promo' => 'Product Promotion',
                        'offer' => 'Offer / Discount',
                        'brand_story' => 'Brand Story',
                        'custom' => 'Custom',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'posting' => 'Posting',
                        'published' => 'Published',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'manual' => 'Manual',
                        'auto_pilot' => 'Auto-Pilot',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('post_now')
                    ->label('Post Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Post to Facebook Now?')
                    ->modalDescription('This will immediately publish this post to your Facebook Page.')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'scheduled', 'failed']))
                    ->action(function ($record) {
                        PublishSocialMediaPostJob::dispatch($record);
                        Notification::make()
                            ->title('Post queued for publishing!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'failed')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'scheduled', 'error_message' => null]);
                        PublishSocialMediaPostJob::dispatch($record);
                        Notification::make()
                            ->title('Post re-queued for publishing.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'failed'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialMediaPosts::route('/'),
            'create' => Pages\CreateSocialMediaPost::route('/create'),
            'edit' => Pages\EditSocialMediaPost::route('/{record}/edit'),
        ];
    }
}

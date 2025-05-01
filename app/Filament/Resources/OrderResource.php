<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\product as ModelsProduct;
use Doctrine\DBAL\Schema\Schema;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Forms\Components\Product;
use Filament\Forms\Components\placeholder;
use Filament\Forms\Components\textcolumn;

use Filament\Forms\Components\numeric;

use function Laravel\Prompts\textarea;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-Shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Order Information')->schema([
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->required(),

                        Select::make('payment_method')
                            ->options([
                                'stripe' => 'Stripe',
                                'cod' => 'Cash On Delivery',

                            ])
                            ->required(),

                        Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                            ])
                            ->default('pending')
                            ->required(),

                        ToggleButtons::make('status')
                            ->options([
                                'new' => 'New',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->colors([
                                'new' => 'info',
                                'processing' => 'warning',
                                'shipped' => 'success',
                                'delivered' => 'success',

                                'cancelled' => 'danger',


                            ])
                            ->icons([
                                'new' => 'heroicon-o-sparkles',
                                'processing' => 'heroicon-o-arrow-path',
                                'shipped' => 'heroicon-o-truck',
                                'delivered' => 'heroicon-o-check-badge',
                                'cancelled' => 'heroicon-o-x-circle',

                            ])
                            ->required()
                            ->default('new')
                            ->inline(),

                        Select::make('currency')
                            ->options([

                                'idr' => 'IDR',
                                'usd' => 'USD',
                            ])
                            ->default(state: 'idr')
                            ->required(),

                        Select::make('shipping_method')
                            ->options([
                                'jnt' => 'J&T',
                                'jne' => 'JNE',
                                'sicepat' => 'SiCepat',

                            ])
                            ->default('jnt')
                            ->required(),

                        textarea::make('notes')
                            ->columnSpanFull()

                    ])->columns(2),
                    Section::make('Order Items')->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([

                                Grid::make(12)->schema([
                                    Select::make('product_id')
                                        ->label('Product')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(4)
                                        ->afterStateUpdated(
                                            fn($state, Set $set) =>
                                            $set('unit_amount', ModelsProduct::find($state)?->price ?? 0)
                                        )
                                        ->afterStateUpdated(
                                            fn($state, Set $set) =>
                                            $set('total_amount', ModelsProduct::find($state)?->price ?? 0)
                                        )
                                        ->required(),


                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->columnSpan(2)
                                        ->minValue(1)
                                        ->default(1)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $set('total_amount', ($get('unit_amount') ?? 0) * ($state ?? 0));
                                        })
                                        ->required(),

                                    TextInput::make('unit_amount')
                                        ->numeric()
                                        ->columnSpan(3)
                                        ->disabled()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $set('total_amount', ($get('quantity') ?? 0) * ($state ?? 0));
                                        })
                                        ->required(),

                                    TextInput::make('total_amount')
                                        ->numeric()
                                        ->columnSpan(3)
                                        ->disabled()
                                        ->dehydrated()
                                        ->required(),
                                ])
                            ])
                    ])->columns(1),
                    Placeholder::make('grand_total_placeholder')
                        ->label('Grand Total')
                        ->reactive()                        // ← bikin repeater reactive
                        ->content(function (Get $get, Set $set) {
                            $total = 0;

                            $repeaters = $get('items');

                            if (!is_array($repeaters)) {
                                $set('grand_total', $total);
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            }

                            foreach ($repeaters as $key => $item) {
                                $amount = $get("items.{$key}.total_amount") ?? 0;
                                $total += $amount;
                            }

                            $set('grand_total', $total);
                            return 'Rp ' . number_format($total, 0, ',', '.');
                        }),
                    Hidden::make(name: 'grand_total')
                        ->default(0),

                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make(name: 'grand_total')
                    ->money('IDR')
                    ->searchable(),
                Tables\Columns\TextColumn::make(name: 'payment_method')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make(name: 'payment_status')
                    ->sortable()
                    ->searchable(),
                tables\Columns\SelectColumn::make(name: 'status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),


            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

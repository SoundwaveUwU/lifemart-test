<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListPossibleIngredientCombinationsRequest;
use App\Models\Ingredient;
use App\Models\IngredientType;
use App\OpenApi\Parameters\ListPossibleDishesParameters;
use App\OpenApi\Responses\ErrorValidationResponse;
use App\OpenApi\Responses\ListPossibleDishesResponse;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class IngredientCombinationController extends Controller
{
    /**
     * List possible dishes with provided ingredients combo
     *
     * @param  ListPossibleIngredientCombinationsRequest  $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    #[OpenApi\Operation]
    #[OpenApi\Parameters(factory: ListPossibleDishesParameters::class)]
    #[OpenApi\Response(factory: ListPossibleDishesResponse::class)]
    #[OpenApi\Response(factory: ErrorValidationResponse::class, statusCode: 422)]
    public function index(ListPossibleIngredientCombinationsRequest $request): JsonResponse
    {
        $ingredients = $request->input('ingredients');

        $counts = collect($ingredients)->countBy();

        foreach ($counts as $code => $count) {
            $realCount = Ingredient::query()
                ->whereHas('type', function ($q) use ($ingredients) {
                    $q->where('code', $ingredients[0]);
                })
                ->count();

            if ($count > $realCount) {
                $validator = Validator::make([], []);
                $validator->errors()->add('ingredients', 'Not enough ingredients to make dish!');
                throw new ValidationException($validator);
            }
        }

        // Первый ингредиент берём за основную таблицу
        $query = Ingredient::query()
            ->whereHas('type', function ($q) use ($ingredients) {
                $q->where('code', $ingredients[0]);
            })
            ->select(['ingredient.id as og.id'])
            ->getQuery();

        unset($ingredients[0]);

        $indexes = [];
        $sum = ['ingredient.price'];
        foreach ($ingredients as $ingredient) {
            if (!isset($indexes[$ingredient])) {
                $indexes[$ingredient] = 1;
            }

            $alias = $ingredient.$indexes[$ingredient];

            $type = Cache::remember(
                "type.$ingredient",
                3600,
                static function () use ($ingredient) {
                    return IngredientType::query()
                        ->where('code', $ingredient)
                        ->firstOrFail();
                }
            );

            $index = $indexes[$ingredient];

            // Добавляем оставшиеся ингредиенты через cross join,
            // Нам важно:
            // 1. Исключить предыдущие ингредиенты (i2.id > i1.id) одного типа
            // (i2.type_id = i1.type_id) по нарастающей по количеству ингредиентов
            // 2. Исключить дубликаты (i3.id != i2.id AND i3.id != i1.id)
            // 3. Дать уникальное имя, чтобы ORM вернула все id
            // 4. Добавить цену в поле сумма
            $query
                ->crossJoin(
                    "ingredient as $alias",
                    function (JoinClause $join) use ($ingredient, $index, $alias, $type) {
                        $join
                            ->where("$alias.type_id", $type->id)
                            ->on('ingredient.id', '<', "$alias.id");

                        if ($index > 1) {
                            for ($i = 1; $i < $index; $i++) {
                                $join
                                    ->on("$alias.id", '!=', "$ingredient$i.id");
                            }

                            $prevIndex = $index - 1;
                            $join->on("$alias.id", '>', "$ingredient$prevIndex.id");
                        }
                    }
                )
                ->addSelect(["$alias.id as {$alias}_id"]);
            $sum [] = "$alias.price";

            $indexes[$ingredient]++;
        }

        $query->addSelect(DB::raw(implode('+', $sum).' as price'));

        $dishes = $query->get();

        // Приводит элементы массива к виду:
        // [
        //   'price' => 420,
        //   'products' => [['type' => 'Тесто', 'value' => 'Тонкое тесто']]
        // ]
        $result = $dishes->map(function ($dish) {
            $ids = collect($dish)->except('price')->values();

            $result = [];

            $result['products'] = $ids->map(function ($id) {
                $ingredient = Cache::remember(
                    "ingredient.$id",
                    3600,
                    static function () use ($id) {
                        return Ingredient::query()
                            ->findOrFail($id);
                    }
                );

                return [
                    'type' => $ingredient->type->title,
                    'value' => $ingredient->title,
                ];
            });
            $result['price'] = (double) $dish->price;

            return $result;
        });

        return response()->json($result);
    }
}

<?php

namespace App\OpenApi\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class ListPossibleDishesResponse extends ResponseFactory
{
    public function build(): Response
    {
        $response = Schema::array()
            ->items(
                Schema::object()
                    ->properties(
                        Schema::number('price')
                            ->format(Schema::FORMAT_DOUBLE),
                        Schema::array()
                            ->items(
                                Schema::object()
                                    ->properties(
                                        Schema::string('type'),
                                        Schema::string('value'),
                                    )
                            )
                    )
            );

        return Response::create('ListOfPossibleDishes')
            ->ok()
            ->description('Successful response')
            ->content(
                MediaType::json()->schema($response)
            );
    }
}

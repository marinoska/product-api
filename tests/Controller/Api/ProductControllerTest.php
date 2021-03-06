<?php declare(strict_types = 1);

namespace App\Tests\Controller\Api;

use App\Entity\Product;
use function json_decode;
use function json_encode;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    public function test_listing_all_products()
    {
        $client = static::createClient();

        $client->request('GET', '/api/products');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function test_getting_single_product()
    {
        $client = static::createClient();

        $client->request('GET', '/api/products/123');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString(
            '{"id":123,"name":"Apple","description":"A tasty snack.","price":49,"taxRate":700}',
            $response->getContent()
        );
    }

    public function test_creating_product()
    {
        $product = new Product();
        $product->id = 789;
        $product->name = 'Orange';
        $product->description = 'A round and orange fruit.';
        $product->price = 19;
        $product->taxRate = 700;
        $productJson = json_encode($product);

        $client = static::createClient();

        $client->request('POST', '/api/products', [], [], [], $productJson);
        $response = $client->getResponse();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertTrue($response->headers->has('Location'));
        $this->assertContains('/api/products/789', $response->headers->get('Location'));

        $client->request('GET', $response->headers->get('Location'));
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString($productJson, $response->getContent());
    }

    public function test_updating_existing_product()
    {
        $product = new Product();
        $product->description = 'Sweet, yellow fruit.';
        $productJson = json_encode($product);

        $client = static::createClient();

        $client->request('POST', '/api/products/234', [], [], [], $productJson);
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $updatedProduct = Product::fromArray(json_decode($response->getContent(), true));

        $this->assertEquals(234, $updatedProduct->id);
        $this->assertSame('Banana', $updatedProduct->name);
        $this->assertSame($product->description, $updatedProduct->description);
        $this->assertEquals(39, $updatedProduct->price);
        $this->assertEquals(700, $updatedProduct->taxRate);
    }

    public function test_deleting_product()
    {
        $client = static::createClient();

        $client->request('DELETE', '/api/products/234');
        $response = $client->getResponse();

        $this->assertEquals(204, $response->getStatusCode());

        $client->request('GET', '/api/products/234');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_not_found_returns_json_error()
    {
        $client = static::createClient();

        $client->request('GET', '/api/spaceships');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString(
            '{"type":"NotFoundHttpException","message":"No route found for \"GET \/api\/spaceships\""}',
            $response->getContent()
        );
    }
}

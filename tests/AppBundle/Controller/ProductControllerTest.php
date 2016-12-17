<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductControllerTest extends WebTestCase
{

    private $products = [];

    private function setUpFixtures(ContainerInterface $container)
    {
        // Delete all products
        $em = $container->get('doctrine.orm.default_entity_manager');
        $query = $em->createQueryBuilder()
            ->delete('AppBundle:Product', 'p')
            ->getQuery();
        $query->execute();

        // Insert two products
        $first = new Product(1);
        $first->setName('First product');
        $first->setDescription('Lorem ipsum dolor sit amet, consectetur adipiscing elit. In ut ligula faucibus, elementum tortor consequat, egestas nulla. Sed sit amet metus venenatis nibh blandit accumsan at sit amet nibh. Quisque finibus varius augue vestibulum efficitur. Donec sagittis libero turpis, eget sollicitudin erat lobortis rhoncus. In hac habitasse platea dictumst. Donec non lorem ut sem tristique faucibus id nec velit. Nunc vestibulum turpis tellus, non hendrerit eros iaculis ultrices. Aliquam erat volutpat. Ut vestibulum metus velit, a imperdiet nulla rhoncus ut. Etiam maximus velit sit amet mi consectetur facilisis. Sed porttitor pretium condimentum.');
        $first->setPrice('45.32');
        $first->setAvailable(true);
        $first->setCreated(\DateTime::createFromFormat(DATE_ATOM, '2015-01-04T12:05:02+01:00'));
        $em->persist($first);

        $first = new Product(2);
        $first->setName('Second product');
        $first->setDescription('Proin finibus consectetur tincidunt. Cras feugiat turpis eget sapien elementum, nec mattis lectus volutpat. Donec in eros ac velit suscipit volutpat ac pretium arcu. Integer non neque consequat, gravida quam a, luctus massa. Ut sit amet eleifend libero, a consectetur dolor. Maecenas varius turpis ac enim ultricies tempor. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Sed semper egestas urna.');
        $first->setPrice('521.09');
        $first->setAvailable(false);
        $first->setCreated(\DateTime::createFromFormat(DATE_ATOM, '2016-11-04T19:05:02+01:00'));
        $em->persist($first);

        $em->flush();

        $this->products = $em->getRepository('AppBundle:Product')->findAll();
    }

    public function testIndexProducts()
    {
        $client = static::createClient();
        $this->setUpFixtures($client->getContainer());

        $client->request('GET', '/product/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $serializer = $client->getContainer()->get('serializer');
        $serialized = $serializer->serialize($this->products, 'json', ['groups' => ['list']]);
        $this->assertEquals($serialized, $client->getResponse()->getContent());
    }

    public function testGetProducts()
    {
        $client = static::createClient();
        $this->setUpFixtures($client->getContainer());

        $serializer = $client->getContainer()->get('serializer');

        foreach($this->products as $product) {
            $client->request('GET', '/product/' . $product->getId());

            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $serialized = $serializer->serialize($product, 'json', ['groups' => ['show']]);
            $this->assertEquals($serialized, $client->getResponse()->getContent());
        }
    }

    public function testPostProduct()
    {
        $client = static::createClient();
        $this->setUpFixtures($client->getContainer());

        $form = [
            'appbundle_product' => [
            ],
        ];

        $client->request('POST', '/product/', $form);

        $this->assertEquals(500, $client->getResponse()->getStatusCode());

        $form = [
            'appbundle_product' => [
                'name' => 'Third Product',
                'available' => 1,
                'price' => '12.32',
                'description' => 'Fisherman\'s friends',
                'created' => '2016-03-02',
            ],
        ];
        $client->request('POST', '/product/', $form);

        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $this->assertContains('"name":"Third Product"', $client->getResponse()->getContent());
        $this->assertContains('"price":12.32', $client->getResponse()->getContent());
    }

    public function testUpdateProduct()
    {
        $client = static::createClient();
        $this->setUpFixtures($client->getContainer());

        $form = [
            'appbundle_product' => [
                'name' => 'Third Product',
                'description' => 'Fisherman\'s friends',
                'available' => 1,
                'price' => '12.32',
                'created' => '2016-03-02',
            ],
        ];

        $pid = $this->products[0]->getId();

        $client->request('POST', '/product/'.$pid.'/edit', $form);

        $this->assertContains('"name":"Third Product"', $client->getResponse()->getContent());
        $this->assertContains('"price":12.32', $client->getResponse()->getContent());
    }

    public function testDeleteProduct()
    {
        $client = static::createClient();
        $this->setUpFixtures($client->getContainer());

        $serializer = $client->getContainer()->get('serializer');

        foreach($this->products as $product) {
            $client->request('GET', '/product/' . $product->getId());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $serialized = $serializer->serialize($product, 'json', ['groups' => ['show']]);
            $this->assertEquals($serialized, $client->getResponse()->getContent());

            $client->request('DELETE', '/product/' . $product->getId());
            $this->assertEquals(204, $client->getResponse()->getStatusCode());

            $client->request('GET', '/product/' . $product->getId());
            $this->assertEquals(404, $client->getResponse()->getStatusCode());
        }

    }
}

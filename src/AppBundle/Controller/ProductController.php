<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Product controller.
 *
 * @Route("product")
 */
class ProductController extends Controller
{
    /**
     * Lists all product entities.
     *
     * @Route("/", name="product_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();

        $products = $em->getRepository('AppBundle:Product')->findAll();

        $data = $this->serialize($products, 'list');

        return Response::create($data, 200, ['Content-type' => 'application/json']);
    }

    /**
     * Creates a new product entity.
     *
     * @Route("/", name="product_new")
     * @Method({"POST"})
     */
    public function newAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Product();
        $form = $this->createForm('AppBundle\Form\ProductType', $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush($product);

            return Response::create($this->serialize($product, 'show'), 201, ['Content-type' => 'application/json']);
        }

        $errors = [];
        foreach ($form->getErrors(true, true) as $formError) {
            $errors[] = $formError->getMessage();
        }

        return JsonResponse::create($form->getErrors(true, true), 400);
    }

    /**
     * Finds and displays a product entity.
     *
     * @Route("/{id}", name="product_show")
     * @Method("GET")
     */
    public function showAction(Product $product)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return Response::create($this->serialize($product, 'show'), 200, ['Content-type' => 'application/json']);
    }

    /**
     * Displays a form to edit an existing product entity.
     *
     * @Route("/{id}", name="product_edit")
     * @Method({"POST"})
     */
    public function editAction(Request $request, Product $product)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $editForm = $this->createForm('AppBundle\Form\ProductType', $product);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return Response::create($this->serialize($product, 'show'), 202, ['Content-type' => 'application/json']);
        }

        $errors = [];
        foreach ($editForm->getErrors(true, true) as $formError) {
            $errors[] = $formError->getMessage();
        }

        return JsonResponse::create($errors, 400);
    }

    /**
     * Deletes a product entity.
     *
     * @Route("/{id}", name="product_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Product $product)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $em->remove($product);
        $em->flush($product);

        return JsonResponse::create(null, 204);
    }

    /**
     * @param $object
     * @param $group

     * @return string
     */
    private function serialize($object, $group)
    {
        $serializer = $this->get('serializer');

        return $serializer->serialize($object,
            'json',
            ['groups' => [$group]]);
    }
}

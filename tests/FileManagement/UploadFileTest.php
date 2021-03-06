<?php

namespace App\Tests\FileManagement;

use App\Repository\ArticleRepository;
use App\Tests\UserHelper;
use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UploadFileTest extends UserHelper
{
    /**
     * Test if a user api without a valid access token cant upload image.
     *
     * @group upload-image
     *
     * @return void
     */
    public function testUnauthorizedApiImageUpload()
    {
        extract(UserHelper::createRandomUser());
        $client = $this->client;

        // api accepts base64image upload
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'email' => $email,
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
        ], [], [
            'HTTP_X-API-TOKEN' => $apiToken,
        ]);

        // unauthaurized request response code
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * Test if a standard user without a valid access token cant upload image.
     *
     * @group upload-image
     *
     * @return void
     */
    public function testUnauthorizedStandardUserImageUpload()
    {
        $client = $this->client;

        // create a random user
        extract(UserHelper::createRandomUser());

        UserHelper::registerUser($client, $email, $apiToken, $password, $firstname, $lastname);

        // api accepts base64image upload
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
            'email' => $email,
        ], [], ['HTTP_X-API-TOKEN' => $apiToken]);

        // unauthaurized request response code
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * Test if a user api with a valid token can upload image.
     *
     * @group upload-image
     *
     * @return void
     */
    public function testBase64ApiUserImageUpload()
    {
        $client = $this->client;

        // get a random image
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        // api accepts base64image upload
        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'email' => $this->user->getEmail(),
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
        ], []);

        // check if file is in DB & is uploaded.
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * Test if a standard user with a valid token can upload image.
     *
     * @group upload-image
     *
     * @return void
     */
    public function testBase64StandardUserImageUpload()
    {
        $client = $this->client;

        // get a random image
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        // api accepts base64image upload
        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
            'email' => $this->user->getEmail(),
        ], [], []);

        // check if file is in DB & is uploaded.
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
    * Test if a standard user with a valid token can upload an article cover.
    *
    * @group upload-image
    *
    * @return void
    */
    public function testBase64ArticleCoverUpload()
    {
        $articleId = $this->createArticle();
        $client = $this->client;

        // get a random image
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        // api accepts base64image upload
        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
            'email' => $this->user->getEmail(),
            'is_article_cover' => true,
            'extra_id' => $articleId
        ], [], []);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $json = static::assertJsonResponse($client, 'id');

        $image = static::$container->get(ImageRepository::class)->findOneBy(['id' => $json['id']]);

        $this->assertNotEmpty($image);
        $this->assertTrue($image->getIsArticleCover());

        $article = static::$container->get(ArticleRepository::class)->findOneBy(['id' => $articleId]);
        $this->assertEquals($article->getCoverId(), $image->getId());

        $this->client->request('GET', '/api/admin/'.$this->admin->getId().'/articles/'.$articleId, [], [], ['HTTP_X-API-TOKEN' => $this->admin->getApiToken()]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        static::assertJsonResponse($this->client, [
        'user_id' => $this->admin->getId(),
        'id' => $articleId,
        'raw_data' => $this->htmlSample,
        'is_draft' => false,
        'title' => $this->articleTitle,
        'description' => $this->articleDescription,
        'cover_id' => $image->getId()
        ]);
    }
}

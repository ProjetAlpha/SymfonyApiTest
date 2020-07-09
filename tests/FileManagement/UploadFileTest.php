<?php

namespace App\Tests\FileManagement;

use App\Tests\UserHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/*
    -- Api request support test :
    const MimeTypesMap = {
            png: 'image/png',
            gif: 'image/gif',
            jpg: 'image/jpg',
            jpeg: 'image/jpeg',
            pdf: 'application/pdf',
            mp4: 'video/mp4',
            doc: 'application/msword',
            docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ppt: 'application/vnd.ms-powerpoint',
            xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    };

    -- Api request support blob file (binary file).
*/

class UploadFileTest extends WebTestCase
{
    /**
     * Test if a user api without a valid access token cant upload image.
     *
     * @return void
     */
    public function testUnauthorizedApiImageUpload()
    {
        $client = static::createClient();

        // api accepts base64image upload
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // unauthorized response code
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * Test if a standard user without a valid access token cant upload image.
     *
     * @return void
     */
    public function testUnauthorizedStandardUserImageUpload()
    {
        $client = static::createClient();

        // create a random user
        extract(UserHelper::createRandomUser());

        UserHelper::registerUser($client, $email, $apiToken, $password);

        // api accepts base64image upload
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
            'email' => $email,
        ], [], []);

        // unauthorized response code
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * Test if a user api with a valid token can upload image.
     *
     * @return void
     */
    public function testBase64ApiUserImageUpload()
    {
        $client = static::createClient();

        // create a random user
        extract(UserHelper::createRandomUser());

        UserHelper::registerUser($client, $email, $apiToken, $password);

        UserHelper::loginUser($client, $email, $password);

        // find api user
        $client->request('POST', '/api/me/', [], [], ['HTTP_X-AUTH-TOKEN' => $apiToken]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // get a random image
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        // api accepts base64image upload
        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
        ], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-AUTH-TOKEN' => $apiToken,
        ]);

        // check if file is in DB & is uploaded.
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * Test if a standard user with a valid token can upload image.
     *
     * @return void
     */
    public function testBase64StandardUserImageUpload()
    {
        $client = static::createClient();

        // create a random user
        extract(UserHelper::createRandomUser());

        UserHelper::registerUser($client, $email, $apiToken, $password);

        UserHelper::loginUser($client, $email, $password);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // get a random image
        $testImagePath = $client->getKernel()->getContainer()->getParameter('image_test');
        $image = new TestImage($testImagePath, true);

        // api accepts base64image upload
        $base64Image = base64_encode(file_get_contents($image->getPath()));

        $client->request('POST', '/api/image/upload', [
            'base64_image' => $base64Image,
            'name' => $image->getName(),
            'extension' => $image->getExtension(),
            'email' => $email,
        ], [], []);

        // check if file is in DB & is uploaded.
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}

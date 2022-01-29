<?php

namespace App\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Operation;
use ApiPlatform\Core\OpenApi\Model\PathItem;
use ApiPlatform\Core\OpenApi\Model\RequestBody;
use ApiPlatform\Core\OpenApi\OpenApi;
use ArrayObject;

class OpenApiFactory implements OpenApiFactoryInterface{
    public function __construct(private OpenApiFactoryInterface $decorate)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorate->__invoke($context);

        foreach($openApi->getPaths()->getPaths() as $key => $path){
            if($path->getGet() && $path->getGet()->getSummary() == 'hidden'){
                $openApi->getPaths()->addPath($key, $path->withGet(null));
            }
        }

        $schema = $openApi->getComponents()->getSecuritySchemes();
        $schema['bearerAuth'] = new ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT'
        ]);

        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['credentials'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'username' => [
                    'type' => 'string',
                    'example' => 'rajoelisonainatiavina@gmail.com'
                ],
                'password' => [
                    'type' => 'string',
                    'example' => '0000'
                ]
            ],
        ]);

        #Remove parameters on a specific item
        $myAccountOperation = $openApi->getPaths()->getPath('/api/myAccount')->getGet()->withParameters([]);
        $myAccountPathItem = $openApi->getPaths()->getPath('/api/myAccount')->withGet($myAccountOperation);
        $openApi->getPaths()->addPath('/api/myAccount',$myAccountPathItem);

        #Adding the path for the authentication on API PLATFORM
        $pathItem = new PathItem(
            post: new Operation(
                tags: ['User'],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/credentials'
                            ]
                        ]
                    ])
                ),
                responses: [
                    '200' => [
                        'description' => 'User connected',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/User-user.read'
                                ]
                            ]
                        ]
                    ]
                ],
                summary: 'Connect with your account'
            )
        );
        
        $openApi->getPaths()->addPath('/api/login', $pathItem);

        $logoutPathItem = new PathItem(
            post: new Operation(
                tags: ['User'],
                responses: [
                    '204' => [
                        'content' => [
                            'application/json' => []
                        ]
                    ]
                ],
                summary: 'Logout from your account'
            )
        );
        $openApi->getPaths()->addPath('/logout', $logoutPathItem);
        return $openApi;
    }
}
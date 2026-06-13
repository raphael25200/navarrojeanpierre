<?php





namespace App\Security;





use Psr\Log\LoggerInterface;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;


use Symfony\Component\HttpFoundation\RedirectResponse;


use Symfony\Component\HttpKernel\Event\RequestEvent;


use Symfony\Component\HttpKernel\KernelEvents;


use Symfony\Component\Routing\RouterInterface;


use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;





class routeTraceSubscriber implements EventSubscriberInterface


{


    private array $protectedRoutes = [


        'admin',


        'app_account'


    ];





    public function __construct(


        private LoggerInterface $logger,


        private TokenStorageInterface $tokenStorage,


        private RouterInterface $router


    ) {}





    public static function getSubscribedEvents(): array


    {


        return [


            KernelEvents::REQUEST => 'onkernelRequest',


        ];
    }





    public function onkernelRequest(RequestEvent $event): void


    {


        // if (!$event->isMainRequest()) {


        //     return;


        // }





        $request = $event->getRequest();


        $route = $request->attributes->get(key: '_route');





        if (null === $route) {


            return;
        }





        // log uniquement certaines routes


        if (!in_array(needle: $route, haystack: ['login', 'app_register'], strict: true)) {


            $this->logger->info(message: sprintf('Route "%s" called', $route), context: [


                'route' => $route,


                'path' => $request->getPathInfo(),


                'method' => $request->getMethod(),


            ]);
        }





        // verifie l'acces aux routes protégées


        if (in_array(needle: $route, haystack: $this->protectedRoutes, strict: true)) {


            $token = $this->tokenStorage->getToken();


            $user = $token?->getUser();





            if (!is_object(value: $user)) {


                // redirection vers login si non connecté


                $loginUrl = $this->router->generate(name: 'login');


                $event->setResponse(response: new RedirectResponse(url: $loginUrl));
            }
        }
    }
}

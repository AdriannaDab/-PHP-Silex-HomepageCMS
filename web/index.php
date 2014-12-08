<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

/* Form */
$app->register(new Silex\Provider\FormServiceProvider());

/* Twig */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../src/Views',
));

/* Validator */
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.domains' => array(),
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());


/* Doctride */
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => '127.0.0.1',
        'dbname' => 'phproject',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8',
    ),
));

/*Session*/
$app->register(new Silex\Provider\SessionServiceProvider());

/*Security*/
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'admin' => array(
             'pattern' => '^.*$',
            'form' => array(
                'login_path' => '/auth/login',
                'check_path' => '/albums/login_check',
                'default_target_path' => '/posts/index',
                'username_parameter' => 'form[username]',
                'password_parameter' => 'form[password]',
            ),
            'logout' => true,
            'anonymous' => true,
            'logout' => array('logout_path' => '/auth/logout'),
            'users' => $app->share(function () use ($app) {
                    return new User\UserProvider($app);
                }),
        ),
    ),
    'security.access_rules' => array(
        array('^/auth/.+$|^/users/add|^/posts.*$|^/comments/.*$|^/pages/.*$|^/categories/.*/.*$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('/projects.*$|^/feedback.*$|^/rates.*$|^/users.*', 'ROLE_USER'),
        array('^/.+$', 'ROLE_ADMIN')
    ),
    'security.role_hierarchy' => array(
        'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ANONYMUS'),
        'ROLE_USER' => array('ROLE_ANONYMUS'),
    ),
));


$app->get('/', function () use ($app) {
    return $app->redirect($app["url_generator"]->generate("/posts/"));
})->bind('/');

date_default_timezone_set('Europe/Warsaw');

$app->mount('/posts/', new Controller\PostsController());
$app->mount('/comments/', new Controller\CommentsController());
$app->mount('/projects/', new Controller\ProjectsController());
$app->mount('/rates/', new Controller\RatesController());
$app->mount('/tags/', new Controller\TagsController());
$app->mount('/users/', new Controller\UsersController());
$app->mount('/auth/', new Controller\AuthController());
$app->mount('/photos/', new Controller\PhotosController());
$app->mount('/feedback/', new Controller\FeedbackController());
$app->mount('/pages/', new Controller\PagesController());
$app->mount('/categories/', new Controller\CategoriesController());


$app->run();
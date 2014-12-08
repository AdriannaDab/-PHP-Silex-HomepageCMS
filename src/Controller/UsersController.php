<?php
 /**
 * Users controller 
 *
 * PHP version 5
 *
 * @category Controller
 * @package  Controller
 * @author   Magdalena Limanówka <m.limanowka@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  SVN: $id$
 * @link     wierzba.wzks.uj.edu.pl/~12_limanowka
 */
namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\UsersModel;

/**
 * Class UsersController
 *
 * @category Controller
 * @package  Controller
 * @author   Magdalena Limanówka <m.limanowka@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version  Release: <package_version>
 * @link     wierzba.wzks.uj.edu.pl/~12_limanowka
 * @uses Silex\Application
 * @uses Silex\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Validator\Constraints
 * @uses Model\UsersModel;
 */
class UsersController implements ControllerProviderInterface
{
    /**
     *
     * UsersModel object.
     *
     * @var $_model
     * $access protected
     */
    protected $_model;


    /**
     * Connection
     *
     * @param Application $app application object
     *
     * @access public
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->_model = new UsersModel($app);
        $userController = $app['controllers_factory'];
        $userController->match('/add/', array($this, 'add'))
            ->bind('/users/add');
        $userController->match('/edit/', array($this, 'edit'))
            ->bind('/users/edit');
        $userController->match('/delete/', array($this, 'delete'))
            ->bind('/users/delete');
        $userController->get('/view/', array($this, 'view'))
            ->bind('/users/view');
        $userController->match('/password/', array($this, 'password'))
            ->bind('/users/password');
        return $userController;
    }


    /**
     * Register new user
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function add(Application $app, Request $request)
    {

        $data = array(
            'signupdate' => date('Y-m-d'),
        );

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'nickname', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'max' => 45,
                            'minMessage' => 
                                'Minimalna ilość znaków to 3',
                            'maxMessage' => 
                                'Maksymalna ilość znaków to 45',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Nick nie jest poprawny',
                        )
                    )
                )
            )
            )
            ->add(
                'password', 'password', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'minMessage' => 'Minimalna ilość znaków to 3',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Hasło nie jest poprawne',
                        )
                    )
                )
            )
            )
            ->add(
                'confirm_password', 'password', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'minMessage' => 
                                'Minimalna ilość znaków to 3',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Hasło nie jest poprawne',
                        )
                    )
                )
            )
            )
            ->add(
                'email', 'email', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 5
                        )
                    ),
                    new Assert\Regex(
                        array(
                            'pattern' => 
                                "/^[a-zA-Z0-9\.\-_]+\@
                                   [a-zA-Z0-9\.\-_]+\.[a-z]{2,4}/",
                            'message' => 'Email nie jest poprawny'
                        )
                    )

                )
            )
            )
            ->add(
                'homesite', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'minMessage' => 'Minimalna ilość znaków to 3',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Adres nie jest poprawny',
                        )
                    ),
                    new Assert\Url()
                )
            )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $check = $this->_model
                ->getUserByLogin($data['nickname']);

            if (!$check) {
                if ($data['password'] === $data['confirm_password']) {

                    $data['password'] 
                        = $app['security.encoder.digest']
                             ->encodePassword("{$data['password']}", '');


                    try {
                        $model = $this->_model->register($data);


                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Konto zostało stworzone'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/auth/login'
                            ), 301
                        );

                    } catch (\Exception $e) {

                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }

                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'warning',
                            'content' => 'Hasła nie są takie same'
                        )
                    );
                    return $app['twig']->render(
                        'users/add.twig', array(
                            'form' => $form->createView()
                        )
                    );
                }
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Użytkownik o tym nicku już istnieje'
                    )
                );
                return $app['twig']->render(
                    'users/add.twig', array(
                        'form' => $form->createView()
                    )
                );
            }

        }

        return $app['twig']->render(
            'users/add.twig', array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * Edit information abaout user
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     *
     */
    public function edit(Application $app, Request $request)
    {
        $id = $this->_model->getIdCurrentUser($app);

        $user = $this->_model->getUserById($id);

        if (count($user)) {

            $data = array(
                'iduser' => $id,
                'email' => $user['email'],
                'homesite' => $user['homesite']
            );

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'email', 'email', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5
                            )
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' =>
                                     "/^[a-zA-Z0-9\.\-_]+\@
                                         [a-zA-Z0-9\.\-_]+\.[a-z]{2,4}/",
                                'message' => 'Email nie jest poprawny'
                            )
                        )
                    )
                )
                )
                ->add(
                    'homesite', 'text', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 3,
                                'minMessage' => 
                                    'Minimalna ilość znaków to 3',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Adres nie jest poprawny',
                            )
                        ),
                        new Assert\Url()
                    )
                )
                )
                ->add(
                    'password', 'password', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 3,
                                'minMessage' => 
                                    'Minimalna ilość znaków to 3',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Hasło nie jest poprawne',
                            )
                        )
                    )
                )
                )
                ->getForm();


            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $password = $app['security.encoder.digest']
                    ->encodePassword("{$data['password']}", '');
                if ($password == $user['password']) {

                    try {
                        $model = $this->_model->editUser($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Informacje zostały zmienione'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/users/view'
                            ), 301
                        );
                    } catch (\Exception $e) {
                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }
                }
            }

            return $app['twig']->render(
                'users/edit.twig', array(
                    'form' => $form->createView()
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono użytkownika'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/users/view'
                ), 301
            );
        }
    }

    /**
     * Delete user account
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function delete(Application $app, Request $request)
    {
        $id = $this->_model->getIdCurrentUser($app);

        $user = $this->_model->getUserById($id);

        $data = array();

        if (count($user)) {
            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'iduser', 'hidden', array(
                    'data' => $id,
                    )
                )
                ->add('Yes', 'submit')
                ->add('No', 'submit')
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                if ($form->get('Yes')->isClicked()) {
                    $data = $form->getData();
                    if ($app['security']->isGranted('ROLE_ADMIN')) {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'danger',
                                'content' => 'Nie można usunąć konta admina'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/'
                            ), 301
                        );
                    } else {
                        try {
                            $model = $this->_model->deleteUser($id);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Konto zostało usunięte'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/'
                                ), 301
                            );
                        } catch (\Exception $e) {
                            $errors[] = 'Coś poszło niezgodnie z planem';
                        }
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/users/edit'
                            ), 301
                        );
                    }
                }
                return $app['twig']->render(
                    'users/delete.twig', array(
                        'form' => $form->createView()
                    )
                );

            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono użytkownika'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/'
                ), 301
            );
        }
    }

    /**
     * View user profile
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function view(Application $app, Request $request)
    {
        $id = $this->_model->getIdCurrentUser($app);
        $user = $this->_model->getUser($id);

        if (count($user)) {
            return $app['twig']->render(
                'users/view.twig', array(
                    'user' => $user
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono użytkownika'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/'
                ), 301
            );
        }
    }

    /**
     * Change passoword
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function password(Application $app, Request $request)
    {

        $id = $this->_model->getIdCurrentUser($app);

        $user = $this->_model->getUserById($id);

        if (count($user)) {

            $data = array();

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'password', 'password', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' => 
                                    'Minimalna ilość znaków to 3',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Hasło nie jest poprawne',
                            )   
                        ),
                    )
                )
                )
                ->add(
                    'confirm_password', 'password', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' => 
                                    'Minimalna ilość znaków to 3',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Hasło nie jest poprawne',
                            )
                        ),
                    )
                )
                )
                ->add(
                    'new_password', 'password', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' => 
                                    'Minimalna ilość znaków to 3',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Hasło nie jest poprawne',
                            )
                        ),
                    )
                )
                )
                ->add(
                    'confirm_new_password', 'password', array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' => 
                                    'Minimalna ilość znaków to 3',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'Hasło nie jest poprawne',
                            )
                        ),
                    )
                )
                )
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $oldPassword = $app['security.encoder.digest']
                    ->encodePassword("{$data['password']}", '');

                if ($oldPassword === $user['password']) {
                    if ($data['new_password']===$data['confirm_new_password']
                        && $data['password'] === $data['confirm_password']
                    ) {

                        $data['new_password'] = $app['security.encoder.digest']
                            ->encodePassword("{$data['new_password']}", '');


                        try {
                            $model = $this->_model->changePassword($data, $id);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Hasło zostałoz mienione'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/auth/login'
                                ), 301
                            );
                        } catch (\Exception $e) {
                            $errors[] = 'Coś poszło niezgodnie z planem';
                        }

                    } else {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'warning',
                                'content' => 'Hasła są różne'
                            )
                        );
                        return $app['twig']->render(
                            'users/edit.twig', array(
                                'form' => $form->createView()
                            )
                        );
                    }
                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'danger',
                            'content' => 'Hasło nie jest poprawne'
                        )
                    );

                }
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono użytkownika'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/auth/login'
                ), 301
            );
        }
        return $app['twig']->render(
            'users/edit.twig', array(
                'form' => $form->createView()
            )
        );
    }
}

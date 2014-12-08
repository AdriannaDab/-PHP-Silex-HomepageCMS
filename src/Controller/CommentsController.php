<?php
 /**
 * Blog comments controller 
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
use Model\CommentsModel;
use Model\UsersModel;
use Model\PostsModel;
 
/**
 * Class CommentsController
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
 * @uses Model\CommentsModel
 * @uses Model\UsersModel
 * @uses Model\PostsModel
 */
class CommentsController implements ControllerProviderInterface
{
    /**
     * CommentsModel object
     *
     * @access protected
     * @var $_model
     */
    protected $_model;

    /**
     * UserModel object
     *
     * @access protected
     * @var $_user
     */
    protected $_user;

    /**
     * PostsModel object
     *
     * @access protected
     * @var $_posts
     */
    protected $_posts;

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
        $this->_model = new CommentsModel($app);
        $this->_user = new UsersModel($app);
        $this->_posts = new PostsModel($app);
        $commentController = $app['controllers_factory'];
        $commentController->get('/{page}/{idpost}/', array($this, 'index'))
            ->value('page', 1)
            ->bind('/comments/');
        $commentController
            ->match('/add/{idpost}', array($this, 'add'))
            ->bind('/comments/add');
        $commentController->match('/edit/{id}', array($this, 'edit'))
            ->bind('/comments/edit');
        $commentController->match('/delete/{id}', array($this, 'delete'))
            ->bind('/comments/delete');
        return $commentController;
    }

    /**
     * View all comments for post
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function index(Application $app, Request $request)
    {
        $id = (int)$request->get('idpost', 0);

        $check = $this->_posts->checkPostId($id);

        if ($check) {

            $comments = $this->_model->getCommentsList($id);

            $_isLogged = $this->_user->_isLoggedIn($app);
            if ($_isLogged) {
                $access = $this->_user->getIdCurrentUser($app);
            } else {
                $access = 0;
            }

            return $app['twig']->render(
                'comments/index.twig', array(
                'comments' => $comments, 'idpost' => $id, 'access' => $access
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }

    /**
     * Add new comment
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function add(Application $app, Request $request)
    {

        $idpost = (int)$request->get('idpost', 0);

        $check = $this->_posts->checkPostId($idpost);

        if ($check) {

            if ($this->_user->_isLoggedIn($app)) {
                $iduser = $this->_user->getIdCurrentUser($app);
            } else {
                $iduser = 0;
            }
            $data = array(
                'published_date' => date('Y-m-d'),
                'idpost' => $idpost,
                'iduser' => $iduser,
            );

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'content', 'textarea', array('required' => false), array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' => 
                                    'Minimalna ilość znaków to 5',
                            )
                        ),
                        new Assert\Type(
                            array(
                                'type' => 'string',
                                'message' => 'tekst nie jest poprawny',
                            )
                        )
                    )
                )
                )
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                try {
                    $model = $this->_model->addComment($data);

                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'success',
                            'content' => 'Komentarz został dodany'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/posts/'
                        ), 301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło niezgodnie z planem';
                }
            }
            return $app['twig']->render(
                'comments/add.twig', array(
                    'form' => $form->createView(), 
                    'idpost' => $idpost
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );
        }
    }

    /**
     * Edit comment
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function edit(Application $app, Request $request)
    {

        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkCommentId($id);

        if ($check) {

            $idCurrentUser = $this->_user->getIdCurrentUser($app);
            $comment = $this->_model->getComment($id);

            if (count($comment)) {

                $data = array(
                    'idcomment' => $id,
                    'published_date' => date('Y-m-d'),
                    'idpost' => $comment['idpost'],
                    'iduser' => $comment['iduser'],
                    'idCurrentUser' => $idCurrentUser,
                    'content' => $comment['content'],
                );

                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'content', 'textarea', array(
                            'required' => false
                        ), array(
                                'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' => 
                                        'Minimalna ilość znaków to 5',
                                )
                            ),
                            new Assert\Type(
                                array(
                                    'type' => 'string',
                                    'message' => 'Tekst nie poprawny.',
                                )
                            )
                        )
                    )
                    )
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    try {
                        $model = $this->_model->editComment($data);

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'content' => 'Komanetarz został zmieniony'
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/posts/'
                            ), 301
                        );
                    } catch (Exception $e) {
                        $errors[] = 'Coś poszło niezgodnie z planem';
                    }
                }
                return $app['twig']->render(
                    'comments/edit.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono komentarza'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/comments/add'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );

        }
    }

    /**
     * Delete comment
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect.
     * @return mixed Generates page.
     */
    public function delete(Application $app, Request $request)
    {
        $id = (int)$request->get('id', 0);

        $check = $this->_model->checkCommentId($id);

        if ($check) {

            $comment = $this->_model->getComment($id);

            $data = array();

            if (count($comment)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'idcomment', 'hidden', array(
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
                        try {
                            $model = $this->_model->deleteComment($data);

                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'type' => 'success',
                                    'content' => 'Komantarz został usunięty'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/posts/'
                                ), 301
                            );
                        } catch (\Exception $e) {
                            $errors[] = 'Coś poszło niezgodnie z planem';
                        }
                    } else {
                        return $app->redirect(
                            $app['url_generator']->generate(
                                '/posts/'
                            ), 301
                        );
                    }
                }
                return $app['twig']->render(
                    'comments/delete.twig', array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono komentarza'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/posts/'
                    ), 301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono komentarza'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/posts/'
                ), 301
            );

        }
    }
}

<?php
 /**
 * Pages controller 
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\PagesModel;

/**
 * Class PagesController
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
 * @uses Model\PagesModel
 */
class PagesController implements ControllerProviderInterface
{
    /**
     * PagesModel object.
     *
     * @var $_model
     * @access protected
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
        $this->_model = new PagesModel($app);
        $pagesController = $app['controllers_factory'];
        $pagesController->match('/aboutme/', array($this, 'aboutme'))
            ->bind('/pages/aboutme');
        $pagesController->match('/contact/', array($this, 'contact'))
            ->bind('/pages/contact');
        $pagesController->match('/editaboutme/', array($this, 'editaboutme'))
            ->bind('/pages/editaboutme');
        $pagesController->match('/editcontact/', array($this, 'editcontact'))
            ->bind('/pages/editcontact');
        return $pagesController;
    }

    /**
     * View About me page
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirects.
     */
    public function aboutme(Application $app, Request $request)
    {
        $page = $this->_model->getPageId('aboutme');
        $idpage = $page['idpage'];

        $page = $this->_model->getInformation($idpage);

        return $app['twig']->render(
            'pages/aboutme.twig', array(
                'pages' => $page
            )
        );
    }

    /**
     * Edit about me page
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function editAboutme(Application $app, Request $request)
    {
        $page = $this->_model->getPage('aboutme');

        $currentPage = $this->_model->getPageId('aboutme');

        $idpage = $currentPage['idpage'];

        $data = array(
            'idpage' => $idpage,
            'imie' => $this->getAttributeName($page, 'imie'),
            'nazwisko' => $this->getAttributeName($page, 'nazwisko'),
            'opis' => $this->getAttributeName($page, 'opis'),
        );

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'imie', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'max' => 45,
                            'minMessage' => 
                                'Minimalna ilość znaków to 3',
                            'maxMessage' => '
                                Maksymalna ilość znaków to {{ limit }}',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Tekst nie jest poprawny',
                        )
                    )
                )
            )
            )
            ->add(
                'nazwisko', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'max' => 45,
                            'minMessage' => 
                                'Minimalna ilość znaków to 3',
                            'maxMessage' => 
                                'Maksymalna ilość znaków to {{ limit }}',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Tekst nie jest poprawny',
                        )
                    )
                )
            )
            )
            ->add(
                'opis', 'textarea', array('required' => false), array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 5,
                            'minMessage' => 'Minimalna ilość znaków to 5',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Tekst nie jest poprawny',
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
                $model = $this->_model->updatePage($data);

                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'success',
                        'content' => 'Informacje zostały dodane'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/pages/aboutme'
                    ), 301
                );
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło niezgodnie z planem';
            }
        }

        return $app['twig']->render(
            'pages/edit_aboutme.twig', array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * View contact page
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function contact(Application $app, Request $request)
    {
        $page = $this->_model->getPageId('contact');

        $idpage = $page['idpage'];

        $page = $this->_model->getInformation($idpage);

        return $app['twig']->render(
            'pages/contact.twig', array(
                'pages' => $page
            )
        );
    }

    /**
     * Edit Contact page
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page or redirect.
     */
    public function editContact(Application $app, Request $request)
    {
        $page = $this->_model->getPage('contact');


        $currentPage = $this->_model->getPageId('contact');

        $idpage = $currentPage['idpage'];

        $data = array(
            'idpage' => $idpage,
            'email' => $this->getAttributeName($page, 'email'),
            'skype' => $this->getAttributeName($page, 'skype'),
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
                        "/^[a-zA-Z0-9\.\-_]+\@[a-zA-Z0-9\.\-_]+\.[a-z]{2,4}/",
                        'message' => 'Email niepoprawny'
                        )
                    )

                )
            )
            )
            ->add(
                'skype', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(
                        array(
                            'min' => 3,
                            'max' => 45,
                            'minMessage' => 
                                'Minimalna ilość znaków to 3',
                            'maxMessage' => 
                                'Maksymalna ilość znaków to {{ limit }}',
                        )
                    ),
                    new Assert\Type(
                        array(
                            'type' => 'string',
                            'message' => 'Tekst jest niepoprawny',
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
                $model = $this->_model->updatePage($data);

                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'success',
                        'content' => 'Informacje zostały dodane'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/pages/contact'
                    ), 301
                );
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło niezgodnie z planem';
            }
        }

        return $app['twig']->render(
            'pages/edit_contact.twig', array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * Get page attribute name
     *
     * @param array  $page      Id page which we want to update
     * @param string $attribute Idattribute which we want to update
     *
     * @access protected
     * @return string or boolean
     */
    protected function getAttributeName($page, $attribute)
    {
        foreach ($page as $row) {
            if ($row['title'] == $attribute) {
                return $row['content'];
            }
        }
        return false;
    }

}

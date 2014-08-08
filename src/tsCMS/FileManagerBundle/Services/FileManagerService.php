<?php
/**
 * Created by PhpStorm.
 * User: plazm
 * Date: 4/16/14
 * Time: 5:04 PM
 */

namespace tsCMS\FileManagerBundle\Services;


use Doctrine\ORM\EntityManager;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Component\Routing\RouterInterface;
use tsCMS\PageBundle\Entity\Page;
use tsCMS\SystemBundle\Event\BuildSiteStructureEvent;
use tsCMS\SystemBundle\Model\SiteStructureAction;
use tsCMS\SystemBundle\Model\SiteStructureGroup;
use tsCMS\SystemBundle\Model\SiteStructureTree;

class FileManagerService {
    /** @var \Doctrine\ORM\EntityManager  */
    private $em;
    /** @var RouterInterface */
    private $router;

    function __construct(EntityManager $em, RouterInterface $router)
    {
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public function getRouter()
    {
        return $this->router;
    }


    public function onBuildSiteStructure(BuildSiteStructureEvent $event) {
        $pagesElement = new SiteStructureGroup("Filarkiv","fa-floppy-o");
        $pagesElement->addElement(new SiteStructureAction("Filarkiv",$this->getRouter()->generate("tscms_filemanager_filemanager_index")));
        $event->addElement($pagesElement);
    }

} 
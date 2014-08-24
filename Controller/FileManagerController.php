<?php

namespace tsCMS\FileManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/filemanager")
 */
class FileManagerController extends Controller
{

    /**
     * @Route("")
     * @Secure("ROLE_ADMIN")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $webPath = $this->get('kernel')->getRootDir() . '/../web';
        $dir = $request->query->get("dir");
        $pathParts = $this->generateDirectoryCrumbs($dir);
        $path = $webPath."/upload/".$dir;

        if ($dir) {
            $dir .= "/";
        }


        $form = $this->createFormBuilder()
            ->add("files","file",array(
                "label" => "filemanager.chooseFiles",
                "attr" => array(
                    "multiple" => true
                )
            ))
            ->add("save","submit",array(
                "label" => "filemanager.upload"
            ))
            ->setAction($this->generateUrl("tscms_filemanager_filemanager_index",array("dir" => $request->query->get("dir"))))
            ->getForm();
        $form->handleRequest($request);


        if ($form->isValid()) {
            /** @var UploadedFile[] $uploadedImages */
            $uploadedImages = $form->getData()['files'];
            foreach($uploadedImages as $uploadedImage) {
                if ($uploadedImage) {
                    $uploadedImage->move($path."/",$uploadedImage->getClientOriginalName());
                }
            }
            return $this->redirect($this->generateUrl("tscms_filemanager_filemanager_index",array("dir" => $request->query->get("dir"))));
        }

        $directoryForm = $this->createFormBuilder()
            ->add("directory","text",array(
                "label" => "filemanager.directory"
            ))
            ->add("save", "submit",array(
                "label" => "filemanager.createDirectory"
            ))
            ->setAction($this->generateUrl("tscms_filemanager_filemanager_index",array("dir" => $request->query->get("dir"))))
            ->getForm();
        $directoryForm->handleRequest($request);

        if ($directoryForm->isValid()) {
            $fs = new Filesystem();
            try {
                $fs->mkdir($path."/".$directoryForm->getData()['directory']);
            } catch(\Exception $e) {

            }

            return $this->redirect($this->generateUrl("tscms_filemanager_filemanager_index",array("dir" => $request->query->get("dir"))));
        }

        $directoryFinder = new Finder();
        $fileFinder = new Finder();


        $directories = $directoryFinder->depth('== 0')->directories()->sortByName()->in($path);
        $files = $fileFinder->depth('== 0')->files()->sortByName()->in($path);

        return array(
            "pathParts" => $pathParts,
            "currentDirectory" => $dir,
            "directories" => $directories,
            "files" => $files,
            "form" => $form->createView(),
            "directoryForm" => $directoryForm->createView()
        );
    }

    private function generateDirectoryCrumbs($path) {
        if ($path == "") {
            return array();
        }

        $split = explode("/", $path);
        $result = array();

        $path = "";
        for ($i = 0, $c = count($split); $i < $c; $i++) {
            $path = $path ? $path . "/" . $split[$i] : $split[$i];
            $result[$path] = $split[$i];
        }


        return $result;
    }

    /**
     * @Route("/images/json", name="tscms_filemanager_filemanager_listimages", options={"expose"=true})
     * @Secure("ROLE_ADMIN")
     */
    public function imageListAction() {
        $webPath = $this->get('kernel')->getRootDir() . '/../web';
        $images = array();

        $locator = new Finder();
        $locator->files()->in($webPath."/upload")->name('/.*\.(jpg|gif|png)/i');

        foreach ($locator as $image) { /** @var $image SplFileInfo */
            $images[] = array(
                "thumb" => "/upload/".$image->getRelativePathname(),
                "image" => "/upload/".$image->getRelativePathname(),
                "title" => $image->getFilename(),
                "folder" => $image->getRelativePath()
            );
        }

        return new JsonResponse($images);
    }

    public function createDirectoryAction() {

    }

    /**
     * @Route("/list.json", name="tscms_filemanager_filepicker_list", options={"expose"=true})
     * @Secure("ROLE_ADMIN")
     */
    public function listAction(Request $request) {
        $folder = $request->query->get("folder","/");
        $selectedFiles = $request->query->get("selectedFiles",array());
        $imagesOnly = $request->query->get("imagesOnly",false);

        $webPath = realpath($this->get('kernel')->getRootDir() . '/../web/upload');
        $directories = array();
        $files = array();

        $locator = new Finder();
        $locator->depth(0);
        $locator->in($webPath.$folder);

        foreach ($locator as $file) { /** @var $file SplFileInfo */
            if ($file->isDir()) {
                $directories[] = array(
                    "selected" => in_array(str_replace($webPath, "", $file->getPathname()), $selectedFiles),
                    "type" => 'folder',
                    "path" => str_replace($webPath, "", $file->getPathname()),
                    "title" => $file->getFilename()
                );
            } else {
                $valid = true;
                $image = false;

                $name = $file->getFilename();
                if (preg_match('/.*\.(jpg|gif|png)/i', $name) !== 0) {
                    $image = true;
                } elseif ($imagesOnly) {
                    $valid = false;
                }

                if ($valid) {
                    $files[] = array(
                        "selected" => in_array(str_replace($webPath, "", $file->getPathname()), $selectedFiles),
                        "type" => $image ? 'image' : 'file',
                        "path" => str_replace($webPath, "", $file->getPathname()),
                        "title" => $file->getFilename()
                    );
                }

            }
        }

        return new JsonResponse(array(
            "directories" => $directories,
            "files" => $files
        ));
    }
}

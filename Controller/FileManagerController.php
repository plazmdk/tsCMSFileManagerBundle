<?php

namespace tsCMS\FileManagerBundle\Controller;

use Gregwar\Image\Image;
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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/filemanager")
 */
class FileManagerController extends Controller
{
    public function indexAction() {}

    /**
     * @Route("/images/json", name="tscms_filemanager_filemanager_listimages", options={"expose"=true})
     * @Secure("ROLE_ADMIN")
     */
    public function imageListAction() {
        $webPath = $this->get('kernel')->getRootDir() . '/../web';
        $images = array();

        $locator = new Finder();
        $locator->files()->in($webPath."/upload")->name('/.*\.(jpg|gif|png)/i');

        /** @var Image $imageHandler */
        $imageHandler = $this->get('image.handling');
        foreach ($locator as $image) { /** @var $image SplFileInfo */

            $images[] = array(
                "thumb" =>  $imageHandler->open("/upload/".$image->getRelativePathname())->cropResize(86,64),
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

        /** @var Image $imageHandler */
        $imageHandler = $this->get('image.handling');

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
                    $data = array(
                        "selected" => in_array(str_replace($webPath, "", $file->getPathname()), $selectedFiles),
                        "type" => $image ? 'image' : 'file',
                        "path" => str_replace($webPath, "", $file->getPathname()),
                        "title" => $file->getFilename()
                    );
                    if ($image) {
                        $data["thumb"] = (string)$imageHandler->open($file->getPathname())->cropResize(86,64);
                    }
                    $files[] = $data;
                }

            }
        }

        return new JsonResponse(array(
            "directories" => $directories,
            "files" => $files
        ));
    }

    /**
     * @Route("/upload")
     * @Secure("ROLE_ADMIN")
     */
    public function uploadAction(Request $request) {
        $webPath = realpath($this->get('kernel')->getRootDir() . '/../web/upload');
        $directory = $request->request->get("directory");

        $destination = realpath($webPath.$directory);
        if (strpos($destination,$webPath) === false) {
            throw new AccessDeniedException("Malicious access to directory");
        }

        /** @var UploadedFile $file */
        $file = $request->files->get("files")[0];

        $file->move($destination,$file->getClientOriginalName());

        return new JsonResponse();
    }

    /**
     * @Route("/createfolder", name="tscms_filemanager_filepicker_createfolder", options={"expose"=true})
     * @Secure("ROLE_ADMIN")
     */
    public function createFolderAction(Request $request) {
        $webPath = realpath($this->get('kernel')->getRootDir() . '/../web/upload');
        $directory = $request->request->get("directory");
        $name = $request->request->get("name");

        $destination = realpath($webPath.$directory)."/".$name;
        if (strpos($destination,$webPath) === false || strpos($name,"..") !== false) {
            throw new AccessDeniedException("Malicious access to directory");
        }

        $fs = new Filesystem();
        $fs->mkdir($destination);

        return new JsonResponse();
    }

    /**
     * @Route("/rename", name="tscms_filemanager_filepicker_rename", options={"expose"=true})
     * @Secure("ROLE_ADMIN")
     */
    public function renameAction(Request $request) {
        $webPath = realpath($this->get('kernel')->getRootDir() . '/../web/upload');
        $path = $request->request->get("path");
        $name = $request->request->get("name");

        $source = realpath($webPath.$path);
        $destination = dirname($source)."/".$name;
        if (strpos($source,$webPath) === false || strpos($destination,$webPath) === false || strpos($name,"..") !== false) {
            throw new AccessDeniedException("Malicious access to directory");
        }

        $fs = new Filesystem();
        $fs->rename($source, $destination);

        return new JsonResponse();
    }

    /**
     * @Route("/delete", name="tscms_filemanager_filepicker_delete", options={"expose"=true})
     * @Secure("ROLE_ADMIN")
     */
    public function deleteAction(Request $request) {
        $webPath = realpath($this->get('kernel')->getRootDir() . '/../web/upload');
        $path = $request->request->get("path");

        $source = realpath($webPath.$path);
        if (strpos($source,$webPath) === false) {
            throw new AccessDeniedException("Malicious access to directory");
        }

        $fs = new Filesystem();
        $fs->remove($source);

        return new JsonResponse();
    }
}

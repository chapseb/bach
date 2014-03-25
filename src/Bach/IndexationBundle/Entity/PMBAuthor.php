<?php
/**
 * Bach PMB Author entity
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Vincent Fleurette <vincent.fleurettes@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
namespace Bach\IndexationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * Bach PMB Author entity
 *
 * @category Indexation
 * @package  Bach
 * @author   Vincent Fleurette <vincent.fleurette@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 *
 * @ORM\Entity
 * @ORM\Table(name="pmb_authors")
 */
class PMBAuthor
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=10)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string",  length=20)
     */
    protected $type_auth;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", nullable=true, length=30)
     */
    protected $function;

    /**
     * @ORM\ManyToOne(targetEntity="PMBFileFormat", inversedBy="authors")
     * @ORM\JoinColumn(name="pmbfile_id", referencedColumnName="uniqid")
     */
    protected $pmbfile;

    /**
     * Main constructor
     *
     * @param string        $type     Entity type
     * @param string        $name     Entity name firstname
     * @param string        $function Entity code function
     * @param PMBFileFormat $pmb      Entity pmbfileformat
     */
    public function __construct($type, $name, $function, $pmb)
    {
        $this->pmbfile = $pmb;
        $this->type_auth = $type;
        $this->name = $name;
        $this->function = self::convertCodeFunction($function);
    }

    /**
     * Parse converter function
     *
     * @param string $code code function
     *
     * @return string
     */
    public static function convertCodeFunction($code)
    {
        $value=null;
        switch ($code) {
        case '005':
            $value = "Acteur";
            break;
        case '010':
            $value = "Adaptateur";
            break;
        case '018':
            $value = "Auteur de l'animation";
            break;
        case '020':
            $value = "Annotateur";
            break;
        case '030':
            $value = "Arrangeur";
            break;
        case '040':
            $value = "Artiste";
            break;
        case '050':
            $value = "Titulaire des droits";
            break;
        case '060':
            $value = "Nom associé";
            break;
        case '065':
            $value = "Commissaire-priseur";
            break;
        case '070':
            $value = "Auteur";
            break;
        case '072':
            $value = "Auteur d'une citation ou d'extraits";
            break;
        case '075':
            $value = "Postfacier, auteur du colophon, etc.";
            break;
        case '080':
            $value = "Préfacier, etc.";
            break;
        case '090':
            $value = "Dialoguiste";
            break;
        case '100':
            $value = "Antécédent bibliographique";
            break;
        case '110':
            $value = "Relieur";
            break;
        case '120':
            $value = "Maquettiste de la reliure";
            break;
        case '130':
            $value = "Maquettiste";
            break;
        case '140':
            $value = "Concepteur de la jaquette";
            break;
        case '150':
            $value = "Concepteur de l'ex-libris";
            break;
        case '160':
            $value = "Libraire";
            break;
        case '170':
            $value = "Calligraphe";
            break;
        case '180':
            $value = "Cartographe";
            break;
        case '190':
            $value = "Censeur";
            break;
        case '195':
            $value = "Chef de choeur";
            break;
        case '200':
            $value = "Chorégraphe";
            break;
        case '202':
            $value = "Artiste de cirque";
            break;
        case '205':
            $value = "Collaborateur";
            break;
        case '207':
            $value = "Humoriste";
            break;
        case '210':
            $value = "Commentateur";
            break;
        case '212':
            $value = "Auteur du commentaire";
            break;
        case '220':
            $value = "Compilateur";
            break;
        case '230':
            $value = "Compositeur";
            break;
        case '240':
            $value = "Compositeur d'imprimerie";
            break;
        case '245':
            $value = "Concepteur";
            break;
        case '250':
            $value = "Chef d'orchestre";
            break;
        case '255':
            $value = "Consultant de projet";
            break;
        case '257':
            $value = "Continuateur";
            break;
        case '260':
            $value = "Détenteur du copyright";
            break;
        case '270':
            $value = "Correcteur";
            break;
        case '273':
            $value = "Commissaire d'exposition";
            break;
        case '274':
            $value = "Danseur";
            break;
        case '280':
            $value = "Dédicataire";
            break;
        case '290':
            $value = "Dédicateur";
            break;
        case '295':
            $value = "Organisme de soutenance";
            break;
        case '300':
            $value = "Metteur en scéne, réalisateur";
            break;
        case '305':
            $value = "Candidat";
            break;
        case '310':
            $value = "Distributeur";
            break;
        case '320':
            $value = "Donateur";
            break;
        case '330':
            $value = "Auteur présumé";
            break;
        case '340':
            $value = "Editeur scientifique";
            break;
        case '350':
            $value = "Graveur";
            break;
        case '360':
            $value = "Aquafortiste";
            break;
        case '365':
            $value = "Expert";
            break;
        case '370':
            $value = "Monteur";
            break;
        case '380':
            $value = "Faussaire";
            break;
        case '390':
            $value = "Ancien possesseur";
            break;
        case '395':
            $value = "Fondateur";
            break;
        case '400':
            $value = "Mécéne (obsoléte)";
            break;
        case '410':
            $value = "Technicien graphique";
            break;
        case '420':
            $value = "Personne honorée";
            break;
        case '430':
            $value = "Enlumineur";
            break;
        case '440':
            $value = "Illustrateur";
            break;
        case '450':
            $value = "Auteur de l'envoi";
            break;
        case '460':
            $value = "Personne interviewée";
            break;
        case '470':
            $value = "Intervieweur";
            break;
        case '475':
            $value = "Collectivité éditrice";
            break;
        case '480':
            $value = "Librettiste";
            break;
        case '490':
            $value = "Détenteur de licence";
            break;
        case '500':
            $value = "Concédant de licence";
            break;
        case '510':
            $value = "Lithographe";
            break;
        case '520':
            $value = "Parolier";
            break;
        case '530':
            $value = "Graveur sur métal";
            break;
        case '535':
            $value = "Mime";
            break;
        case '540':
            $value = "Moniteur";
            break;
        case '545':
            $value = "Musicien";
            break;
        case '550':
            $value = "Narrateur";
            break;
        case '555':
            $value = "Opposant";
            break;
        case '557':
            $value = "Organisateur de réunion, de conférence";
            break;
        case '560':
            $value = "Instigateur";
            break;
        case '570':
            $value = "Autre";
            break;
        case '580':
            $value = "Fabricant du papier";
            break;
        case '582':
            $value = "Demandeur de brevet";
            break;
        case '584':
            $value = "Inventeur de brevet";
            break;
        case '587':
            $value = "Titulaire de brevet";
            break;
        case '590':
            $value = "Interpréte";
            break;
        case '595':
            $value = "Directeur de la recherche";
            break;
        case '600':
            $value = "Photographe";
            break;
        case '605':
            $value = "Présentateur";
            break;
        case '610':
            $value = "Imprimeur";
            break;
        case '620':
            $value = "Imprimeur de gravures";
            break;
        case '630':
            $value = "Producteur";
            break;
        case '632':
            $value = "Directeur artistique";
            break;
        case '633':
            $value = "Membre de l'équipe de production";
            break;
        case '635':
            $value = "Programmeur";
            break;
        case '637':
            $value = "Gestionnaire de projet";
            break;
        case '640':
            $value = "Correcteur sur épreuves";
            break;
        case '650':
            $value = "Editeur commercial";
            break;
        case '651':
            $value = "Directeur de publication, rédacteur en chef";
            break;
        case '655':
            $value = "Marionnettiste";
            break;
        case '660':
            $value = "Destinataire de lettres";
            break;
        case '670':
            $value = "Ingénieur du son";
            break;
        case '673':
            $value = "Responsable de l'équipe de recherche";
            break;
        case '675':
            $value = "Responsable du compte-rendu critique";
            break;
        case '677':
            $value = "Membre de l'équipe de recherche";
            break;
        case '680':
            $value = "Rubricateur";
            break;
        case '690':
            $value = "Scénariste";
            break;
        case '695':
            $value = "Conseiller scientifique";
            break;
        case '700':
            $value = "Copiste, scribe";
            break;
        case '705':
            $value = "Sculpteur";
            break;
        case '710':
            $value = "Secrétaire";
            break;
        case '720':
            $value = "Signataire";
            break;
        case '721':
            $value = "Chanteur (Musique classique)";
            break;
        case '723':
            $value = "Parraineur";
            break;
        case '725':
            $value = "Organisme de normalisation";
            break;
        case '726':
            $value = "Cascadeur";
            break;
        case '727':
            $value = "Directeur de thése";
            break;
        case '730':
            $value = "Traducteur";
            break;
        case '740':
            $value = "Concepteur de caractéres";
            break;
        case '750':
            $value = "Typographe";
            break;
        case '753':
            $value = "Vendeur";
            break;
        case '755':
            $value = "Chanteur, exécutant vocal";
            break;
        case '760':
            $value = "Graveur sur bois";
            break;
        case '770':
            $value = "Auteur du matériel d'accompagnement";
            break;
        default:
            $value=$code;
            break;
        }
        return $value;
    }
    /**
     * Get uniqid
     *
     * @return integer
     */
    public function getUniqid()
    {
        return $this->uniqid;
    }

    /**
     * Set author type
     *
     * @param string $type_auth Author type
     *
     * @return PMBAutor
     */
    public function setTypeAuth($type_auth)
    {
        $this->type_auth = $type_auth;
        return $this;
    }

    /**
     * Get author type
     *
     * @return string
     */
    public function getTypeAuth()
    {
        return $this->type_auth;
    }

    /**
     * Set lastname
     *
     * @param string $lastname Last name
     *
     * @return PMBAuthor
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set firstname
     *
     * @param string $firstname First name
     *
     * @return PMBAuthor
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set codefonction
     *
     * @param string $codefonction Function code
     *
     * @return PMBAuthor
     */
    public function setCodefonction($codefonction)
    {
        $this->codefonction = $codefonction;
        return $this;
    }

    /**
     * Get codefonction
     *
     * @return string
     */
    public function getCodefonction()
    {
        return $this->codefonction;
    }

    /**
     * Set PMB file
     *
     * @param PMBFileFormat $pmbfile PMB file
     *
     * @return PMBAuthor
     */
    public function setPmbfile(PMBFileFormat $pmbfile)
    {
        $this->pmbfile = $pmbfile;
        return $this;
    }

    /**
     * Get PMB file
     *
     * @return PMBFileFormat
     */
    public function getPmbfile()
    {
        return $this->pmbfile;
    }
}

<?php
/**
 * Regroupe les fonctions d'acc�s aux donn�es.
 * @package default
 * @author Arthur Martin
 * @todo Fonctions retournant plusieurs lignes sont � r��crire.
 */

/**
 * Se connecte au serveur de donn�es MySql.
 * Se connecte au serveur de donn�es MySql � partir de valeurs
 * pr�d�finies de connexion (h�te, compte utilisateur et mot de passe).
 * Retourne l'identifiant de connexion si succ�s obtenu, le bool�en false
 * si probl�me de connexion.
 * @return resource identifiant de connexion
 */
function connecterServeurBD() {
    $cnx = NULL;
    $dsn = "mysql:dbname=gsb_frais;host=127.0.0.1:3306";
    $user = "root";
    $password = "nOWO5kPppdFZgnpujwdO";
    try {
      $cnx = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'', PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    } catch (PDOException $e) {
      echo 'Connexion échouée : ' . $e->getMessage();
    }
    return $cnx;
}

/**
 * S�lectionne (rend active) la base de donn�es.
 * S�lectionne (rend active) la BD pr�d�finie gsb_frais sur la connexion
 * identifi�e par $idCnx. Retourne true si succ�s, false sinon.
 * @param resource $idCnx identifiant de connexion
 * @return boolean succ�s ou �chec de s�lection BD
 */
function activerBD($idCnx) {
//    $bd = "gsb_valide";
    $query = "SET CHARACTER SET utf8";
    $res = NULL;
    // Modification du jeu de caract�res de la connexion
    try {
        $res = $idCnx->query($query);
    } catch (PDOException $e) {
      echo 'Erreur lors de la modification du jeu de caractère : ' . $e->getMessage();
    }
//    $res = mysql_query($query, $idCnx);
//    $ok = mysql_select_db($bd, $idCnx);
    return $res;
}

/**
 * Ferme la connexion au serveur de donn�es.
 * Ferme la connexion au serveur de donn�es identifi�e par l'identifiant de
 * connexion $idCnx.
 * @param resource $idCnx identifiant de connexion
 * @return void
 */
function deconnecterServeurBD($idCnx) {
    unset($idCnx);
}

/**
 * Echappe les caract�res sp�ciaux d'une cha�ne.
 * Envoie la cha�ne $str �chapp�e, c�d avec les caract�res consid�r�s sp�ciaux
 * par MySql (tq la quote simple) pr�c�d�s d'un \, ce qui annule leur effet sp�cial
 * @param string $str cha�ne � �chapper
 * @return string cha�ne �chapp�e
 */
function filtrerChainePourBD($str) {
    if ( ! get_magic_quotes_gpc() ) {
        // si la directive de configuration magic_quotes_gpc est activ�e dans php.ini,
        // toute cha�ne re�ue par get, post ou cookie est d�j� �chapp�e
        // par cons�quent, il ne faut pas �chapper la cha�ne une seconde fois
        $str = mysql_real_escape_string($str);
    }
    return $str;
}

/**
 * Fournit les informations sur un visiteur demand�.
 * Retourne les informations du visiteur d'id $unId sous la forme d'un tableau
 * associatif dont les cl�s sont les noms des colonnes(id, nom, prenom).
 * @param resource $idCnx identifiant de connexion
 * @param string $unId id de l'utilisateur
 * @return array  tableau associatif du visiteur
 */

function obtenirDetailVisiteur($idCnx, $unId) {
    $ligne = NULL;
    try {
      $req = "SELECT id, nom, prenom FROM Visiteur WHERE id = :id";
      $statement = $idCnx->prepare($req);

      $res = $statement->execute(array(':id' => $unId));
      if ($res) {
          $ligne = $statement->fetch();
          $statement->closeCursor();
      }
    } catch (PDOException $e) {
      echo "Erreur lors de l'obtention des détails du visiteur: " . $e->getMessage();
    }
    return $ligne;
}

/**
 * Fournit les informations d'une fiche de frais.
 * Retourne les informations de la fiche de frais du mois de $unMois (MMAAAA)
 * sous la forme d'un tableau associatif dont les cl�s sont les noms des colonnes
 * (nbJustitificatifs, idEtat, libelleEtat, dateModif, montantValide).
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return array tableau associatif de la fiche de frais
 */

function obtenirDetailFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $ligne = NULL;
    try {
      $req = "SELECT IFNULL(nbJustificatifs,0) as nbJustificatifs, Etat.id as idEtat, libelle as libelleEtat, dateModif, montantValide";
      $req .= " FROM FicheFrais INNER JOIN Etat ON idEtat = Etat.id";
      $req .= " WHERE idVisiteur = :visiteur AND mois = :mois";

      $statement = $idCnx->prepare($req);

      $res = $statement->execute(array(':visiteur' => $unIdVisiteur, ':mois' => $unMois));
      if ($res) {
        $ligne = $statement->fetch();
      }

    } catch (PDOException $e) {
      echo "Erreur lors de l'obtention des détails d'une fiche de frais: " . $e->getMessage();
    }
    return $ligne ;
}

/**
 * V�rifie si une fiche de frais existe ou non.
 * Retourne true si la fiche de frais du mois de $unMois (MMAAAA) du visiteur
 * $idVisiteur existe, false sinon.
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return bool�en existence ou non de la fiche de frais
 */

function existeFicheFrais($idCnx, $unMois, $unIdVisiteur) {
  $ligne = NULL;
  try {
    $req = "SELECT idVisiteur FROM FicheFrais";
    $req .= " WHERE idVisiteur = :visiteur AND mois = :mois";
    $statement = $idCnx->prepare($req);
    $res = $statement->execute(array(':visiteur' => $unIdVisiteur, ':mois' => $unMois));

    if ($res) {
        $ligne = $statement->fetch();
        $statement->closeCursor();
    }
  } catch (PDOException $e) {
    echo "Erreur lors du test d'existence d'une fiche de frais " . $e->getMessage();
  }
  // si $ligne est un tableau, la fiche de frais existe, sinon elle n'exsite pas
  return is_array($ligne) ;
}

/**
 * Fournit le mois de la derni�re fiche de frais d'un visiteur.
 * Retourne le mois de la derni�re fiche de frais du visiteur d'id $unIdVisiteur.
 * @param resource $idCnx identifiant de connexion
 * @param string $unIdVisiteur id visiteur
 * @return string dernier mois sous la forme AAAAMM
 */

function obtenirDernierMoisSaisi($idCnx, $unIdVisiteur) {
  $dernierMois = NULL;
  try {
    $req = "SELECT MAX(mois) AS dernierMois FROM FicheFrais";
    $req .= " WHERE idVisiteur = :visiteur";

    $statement = $idCnx->prepare($req);
    $res = $statement->execute(array(':visiteur' => $unIdVisiteur));

    if ($res) {
        $ligne = $statement->fetch();
        $dernierMois = $ligne["dernierMois"];
        $statement->closeCursor();
    }
  } catch (PDOException $e) {
    echo "Erreur lors de l'obtention du dernier mois saisi " . $e->getMessage();
  }
	return $dernierMois;
}

/**
 * Ajoute une nouvelle fiche de frais et les �l�ments forfaitis�s associ�s,
 * Ajoute la fiche de frais du mois de $unMois (MMAAAA) du visiteur
 * $idVisiteur, avec les �l�ments forfaitis�s associ�s dont la quantit� initiale
 * est affect�e � 0. Cl�t �ventuellement la fiche de frais pr�c�dente du visiteur.
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return void
 */

function ajouterFicheFrais($idCnx, $unMois, $unIdVisiteur) {

  // modification de la derni�re fiche de frais du visiteur
  $dernierMois = obtenirDernierMoisSaisi($idCnx, $unIdVisiteur);
	$laDerniereFiche = obtenirDetailFicheFrais($idCnx, $dernierMois, $unIdVisiteur);
	if ( is_array($laDerniereFiche) && $laDerniereFiche['idEtat']=='CR'){
		modifierEtatFicheFrais($idCnx, $dernierMois, $unIdVisiteur, 'CL');
	}
  try {
    // ajout de la fiche de frais � l'�tat Cr��
    $req = "INSERT INTO FicheFrais (idVisiteur, mois, nbJustificatifs, montantValide, idEtat, dateModif)";
    $req .= " VALUES (:visiteur, :mois, 0, NULL, 'CR', '" . date("Y-m-d") . "')";

    $statement = $idCnx->prepare($req);
    $res = $statement->execute(array(':visiteur' => $unIdVisiteur, ':mois' => $unMois));
    if (!$res) {
      error_log("Insertion manquee pour le mois " . $mois);
    }
    // ajout des �l�ments forfaitis�s
    $req = "SELECT id FROM FraisForfait";
    $statement = $idCnx->query($req);
    if ( $statement ) {
        $ligne = $statement->fetch();
        while ( is_array($ligne) ) {
            $idFraisForfait = $ligne["id"];
            // insertion d'une ligne frais forfait dans la base
            $req = "INSERT INTO LigneFraisForfait (idVisiteur, mois, idFraisForfait, quantite)";
            $req .= "VALUES (:visiteur,:mois ,:frais ,0)";
            $statement = $idCnx->prepare($req);
            $res = $statement->execute(array(':visiteur' => $unIdVisiteur, ':mois' => $unMois, ':frais' => $idFraisForfait));

            // passage au frais forfait suivant
            $ligne = $statement->fetch();
        }
        $statement->closeCursor();
    }
  } catch (PDOException $e) {
    echo 'Erreur lors de l ajout de la fiche de frais: ' . $e->getMessage();
  }
}

/**
 * Retourne le texte de la requ�te select concernant les mois pour lesquels un
 * visiteur a une fiche de frais.
 *
 * La requ�te de s�lection fournie permettra d'obtenir les mois (AAAAMM) pour
 * lesquels le visiteur $unIdVisiteur a une fiche de frais.
 * @param string $unIdVisiteur id visiteur
 * @return string texte de la requ�te select
 */

function obtenirReqMoisFicheFrais($unIdVisiteur) {
    $req = "SELECT FicheFrais.mois AS mois FROM FicheFrais WHERE FicheFrais.idvisiteur ='"
            . $unIdVisiteur . "' ORDER BY FicheFrais.mois DESC ";
    error_log($req);
    return $req ;
}

/**
 * Retourne le texte de la requ�te select concernant les �l�ments forfaitis�s
 * d'un visiteur pour un mois donn�s.
 *
 * La requ�te de s�lection fournie permettra d'obtenir l'id, le libell� et la
 * quantit� des �l�ments forfaitis�s de la fiche de frais du visiteur
 * d'id $idVisiteur pour le mois $mois
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return string texte de la requ�te select
 */
function obtenirReqEltsForfaitFicheFrais() {
    $req = "SELECT idFraisForfait, libelle, quantite FROM LigneFraisForfait";
    $req .= " INNER JOIN FraisForfait ON FraisForfait.id = LigneFraisForfait.idFraisForfait";
    $req .= " WHERE idVisiteur = :visiteur AND mois = :mois";
    return $req;
}
/**
 * Retourne le texte de la requ�te select concernant les �l�ments hors forfait
 * d'un visiteur pour un mois donn�s.
 *
 * La requ�te de s�lection fournie permettra d'obtenir l'id, la date, le libell�
 * et le montant des �l�ments hors forfait de la fiche de frais du visiteur
 * d'id $idVisiteur pour le mois $mois
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return string texte de la requ�te select
 */
function obtenirReqEltsHorsForfaitFicheFrais() {
    $req = "SELECT id, date, libelle, montant FROM LigneFraisHorsForfait";
    $req .= " WHERE idVisiteur = :visiteur AND mois = :mois";
    return $req;
}

/**
 * Supprime une ligne hors forfait.
 * Supprime dans la BD la ligne hors forfait d'id $unIdLigneHF
 * @param resource $idCnx identifiant de connexion
 * @param string $idLigneHF id de la ligne hors forfait
 * @return void
 */
function supprimerLigneHF($idCnx, $unIdLigneHF) {
  try {
    $req = "DELETE FROM LigneFraisHorsForfait WHERE id = :ligne_hf";
    $statement = $idCnx->prepare($req);
    $res = $statement->execute(array(':ligne_hf' => $unIdLigneHF));
  } catch (PDOException $e) {
    echo "Erreur lors de la suppression d'une ligne hors forfait : " . $e->getMessage();
  }
}

/**
 * Ajoute une nouvelle ligne hors forfait.
 * Ins�re dans la BD la ligne hors forfait de libell� $unLibelleHF du montant
 * $unMontantHF ayant eu lieu � la date $uneDateHF pour la fiche de frais du mois
 * $unMois du visiteur d'id $unIdVisiteur
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demand� (AAMMMM)
 * @param string $unIdVisiteur id du visiteur
 * @param string $uneDateHF date du frais hors forfait
 * @param string $unLibelleHF libell� du frais hors forfait
 * @param double $unMontantHF montant du frais hors forfait
 * @return void
 */
function ajouterLigneHF($idCnx, $unMois, $unIdVisiteur, $uneDateHF, $unLibelleHF, $unMontantHF) {
  $uneDateHF =convertirDateFrancaisVersAnglais($uneDateHF);
    try {
      $req = "INSERT INTO LigneFraisHorsForfait(idVisiteur, mois, date, libelle, montant)";
      $req .= " VALUES (:visiteur, :mois, :datehf, :libelle, :montant)";
      $statement = $idCnx->prepare($req);
      $res = $statement->execute(array(':visiteur' => $unIdVisiteur, ':mois' => $unMois, ':datehf' => $uneDateHF, ':libelle' => $unLibelleHF, ':montant' => $unMontantHF));
    } catch (PDOException $e) {
     echo "Erreur lors de l'ajout d une ligne hors forfait : " . $e->getMessage();
  }
}

/**
 * Modifie les quantit�s des �l�ments forfaitis�s d'une fiche de frais.
 * Met � jour les �l�ments forfaitis�s contenus
 * dans $desEltsForfaits pour le visiteur $unIdVisiteur et
 * le mois $unMois dans la table LigneFraisForfait, apr�s avoir filtr�
 * (annul� l'effet de certains caract�res consid�r�s comme sp�ciaux par
 *  MySql) chaque donn�e
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur  id visiteur
 * @param array $desEltsForfait tableau des quantit�s des �l�ments hors forfait
 * avec pour cl�s les identifiants des frais forfaitis�s
 * @return void
 */
function modifierEltsForfait($idCnx, $unMois, $unIdVisiteur, $desEltsForfait) {
  try {
    foreach ($desEltsForfait as $idFraisForfait => $quantite) {
        $req = "UPDATE LigneFraisForfait SET quantite = :qte";
        $req .= " WHERE idVisiteur = :visiteur AND mois = :mois and idFraisForfait = :idfrais";
        $statement = $idCnx->prepare($req);
        $res = $statement->execute(array(':qte' => $quantite, ':visiteur' => $unIdVisiteur, ':mois' => $unMois, ':idfrais' => $idFraisForfait));
    }
    $statement->closeCursor();
  } catch (PDOException $e) {
    echo 'Erreur lors de la modification des éléments de forfaitisés : ' . $e->getMessage();
  }
}

/**
 * Contr�le les informations de connexionn d'un utilisateur.
 * V�rifie si les informations de connexion $unLogin, $unMdp sont ou non valides.
 * Retourne les informations de l'utilisateur sous forme de tableau associatif
 * dont les cl�s sont les noms des colonnes (id, nom, prenom, login, mdp)
 * si login et mot de passe existent, le bool�en false sinon.
 * @param resource $idCnx identifiant de connexion
 * @param string $unLogin login
 * @param string $unMdp mot de passe
 * @return array tableau associatif ou bool�en false
 */
function verifierInfosConnexion($idCnx, $unLogin, $unMdp) {
  $ligne = NULL;
  // le mot de passe est crypt� dans la base avec la fonction de hachage md5
  try {
    $req = "SELECT id, nom, prenom, login, mdp FROM Visiteur";
    $req .= " WHERE login = :login AND mdp = :mdp";
    $statement = $idCnx->prepare($req);
    $res = $statement->execute(array(':login' => $unLogin, ':mdp' => $unMdp));
    if ($res) {
        $ligne = $statement->fetch();
        $statement->closeCursor();
    }
  } catch (PDOException $e) {
    echo 'Erreur lors de la vérification des informations de connexion : ' . $e->getMessage();
  }
  return $ligne;
}

/**
 * Modifie l'�tat et la date de modification d'une fiche de frais

 * Met � jour l'�tat de la fiche de frais du visiteur $unIdVisiteur pour
 * le mois $unMois � la nouvelle valeur $unEtat et passe la date de modif �
 * la date d'aujourd'hui
 * @param resource $idCnx identifiant de connexion
 * @param string $unIdVisiteur
 * @param string $unMois mois sous la forme aaaamm
 * @return void
 */
function modifierEtatFicheFrais($idCnx, $unMois, $unIdVisiteur, $unEtat) {
  try {
    $req = "UPDATE FicheFrais SET idEtat = :etat, dateModif = NOW()";
    $req .= " WHERE idVisiteur = :visiteur AND mois = :mois";

    $statement = $idCnx->prepare($req);
    $res = $statement->execute(array(':etat' => $unEtat, ':visiteur' => $unIdVisiteur, ':mois' => $unMois));
  } catch (PDOException $e) {
    echo 'Erreur lors de la modification de l état de la date de la fiche de frais : ' . $e->getMessage();
  }
}
?>

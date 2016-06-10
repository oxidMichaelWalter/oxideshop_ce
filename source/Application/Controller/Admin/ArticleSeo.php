<?php
/**
 * This file is part of OXID eShop Community Edition.
 *
 * OXID eShop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eShop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2016
 * @version   OXID eShop CE
 */

namespace OxidEsales\Eshop\Application\Controller\Admin;

use oxRegistry;
use oxDb;
use oxField;

/**
 * Article seo config class
 */
class ArticleSeo extends \Object_Seo
{

    /**
     * Chosen category id
     *
     * @var string
     */
    protected $_sActCatId = null;

    /**
     * Product selections (categories, vendors etc assigned)
     *
     * @var array
     */
    protected $_aSelectionList = null;

    /**
     * Returns active selection type - oxcategory, oxmanufacturer, oxvendor
     *
     * @return string
     */
    public function getActCatType()
    {
        $sType = false;
        $aData = oxRegistry::getConfig()->getRequestParameter("aSeoData");
        if ($aData && isset($aData["oxparams"])) {
            $oStr = getStr();
            $iEndPos = $oStr->strpos($aData["oxparams"], "#");
            $sType = $oStr->substr($aData["oxparams"], 0, $iEndPos);
        } elseif ($aList = $this->getSelectionList()) {
            reset($aList);
            $sType = key($aList);
        }

        return $sType;
    }

    /**
     * Returns active category (manufacturer/vendor) language id
     *
     * @return int
     */
    public function getActCatLang()
    {
        if (oxRegistry::getConfig()->getRequestParameter("editlanguage") !== null) {
            return $this->_iEditLang;
        }

        $iLang = false;
        $aData = oxRegistry::getConfig()->getRequestParameter("aSeoData");
        if ($aData && isset($aData["oxparams"])) {
            $oStr = getStr();
            $iStartPos = $oStr->strpos($aData["oxparams"], "#");
            $iEndPos = $oStr->strpos($aData["oxparams"], "#", $iStartPos + 1);
            $iLang = $oStr->substr($aData["oxparams"], $iEndPos + 1);
        } elseif ($aList = $this->getSelectionList()) {
            $aList = reset($aList);
            $iLang = key($aList);
        }

        return (int) $iLang;
    }

    /**
     * Returns active category (manufacturer/vendor) id
     *
     * @return false|string
     */
    public function getActCatId()
    {
        $sId = false;
        $aData = oxRegistry::getConfig()->getRequestParameter("aSeoData");
        if ($aData && isset($aData["oxparams"])) {
            $oStr = getStr();
            $iStartPos = $oStr->strpos($aData["oxparams"], "#");
            $iEndPos = $oStr->strpos($aData["oxparams"], "#", $iStartPos + 1);
            $iLen = $oStr->strlen($aData["oxparams"]);

            $sId = $oStr->substr($aData["oxparams"], $iStartPos + 1, $iEndPos - $iLen);
        } elseif ($aList = $this->getSelectionList()) {
            $oItem = reset($aList[$this->getActCatType()][$this->getActCatLang()]);

            $sId = $oItem->getId();
        }

        return $sId;
    }

    /**
     * Returns product selections array [type][language] (categories, vendors etc assigned)
     *
     * @return array
     */
    public function getSelectionList()
    {
        if ($this->_aSelectionList === null) {
            $this->_aSelectionList = array();

            $oProduct = oxNew('OxidEsales\Eshop\Application\Model\Article');
            $oProduct->load($this->getEditObjectId());

            if ($oCatList = $this->_getCategoryList($oProduct)) {
                $this->_aSelectionList["oxcategory"][$this->_iEditLang] = $oCatList;
            }

            if ($oVndList = $this->_getVendorList($oProduct)) {
                $this->_aSelectionList["oxvendor"][$this->_iEditLang] = $oVndList;
            }

            if ($oManList = $this->_getManufacturerList($oProduct)) {
                $this->_aSelectionList["oxmanufacturer"][$this->_iEditLang] = $oManList;
            }
        }

        return $this->_aSelectionList;
    }

    /**
     * Returns array of product categories
     *
     * @param oxarticle $oArticle Article object
     *
     * @return array
     */
    protected function _getCategoryList($oArticle)
    {
        $sMainCatId = false;
        if ($oMainCat = $oArticle->getCategory()) {
            $sMainCatId = $oMainCat->getId();
        }

        $aCatList = array();
        $iLang = $this->getEditLang();

        // adding categories
        $sView = getViewName('oxobject2category');
        $oDb = oxDb::getDb(oxDB::FETCH_MODE_ASSOC);
        $sSqlForPriceCategories = $oArticle->getSqlForPriceCategories('oxid');
        $sQuotesArticleId = $oDb->quote($oArticle->getId());
        $sQ = "select oxobject2category.oxcatnid as oxid from {$sView} as oxobject2category " .
              "where oxobject2category.oxobjectid=" . $sQuotesArticleId . " union " . $sSqlForPriceCategories;

        $oRs = $oDb->select($sQ);
        if ($oRs != false && $oRs->recordCount() > 0) {
            while (!$oRs->EOF) {
                $oCat = oxNew('oxCategory');
                if ($oCat->loadInLang($iLang, current($oRs->fields))) {
                    if ($sMainCatId == $oCat->getId()) {
                        $sSuffix = oxRegistry::getLang()->translateString('(main category)', $this->getEditLang());
                        $sTitleField = 'oxcategories__oxtitle';
                        $sTitle = $oCat->$sTitleField->getRawValue() . " " . $sSuffix;
                        $oCat->$sTitleField = new oxField($sTitle, oxField::T_RAW);
                    }
                    $aCatList[] = $oCat;
                }
                $oRs->moveNext();
            }
        }

        return $aCatList;
    }

    /**
     * Returns array containing product vendor object
     *
     * @param oxArticle $oArticle Article object
     *
     * @return array
     */
    protected function _getVendorList($oArticle)
    {
        if ($oArticle->oxarticles__oxvendorid->value) {
            $oVendor = oxNew('oxvendor');
            if ($oVendor->loadInLang($this->getEditLang(), $oArticle->oxarticles__oxvendorid->value)) {
                return array($oVendor);
            }
        }
    }

    /**
     * Returns array containing product manufacturer object
     *
     * @param oxarticle $oArticle Article object
     *
     * @return array
     */
    protected function _getManufacturerList($oArticle)
    {
        if ($oArticle->oxarticles__oxmanufacturerid->value) {
            $oManufacturer = oxNew('oxmanufacturer');
            if ($oManufacturer->loadInLang($this->getEditLang(), $oArticle->oxarticles__oxmanufacturerid->value)) {
                return array($oManufacturer);
            }
        }
    }

    /**
     * Returns active category object, used for seo url getter
     *
     * @return oxcategory | null
     */
    public function getActCategory()
    {
        $oCat = oxNew('oxCategory');

        return ($oCat->load($this->getActCatId())) ? $oCat : null;
    }

    /**
     * Returns active vendor object if available
     *
     * @return oxvendor | null
     */
    public function getActVendor()
    {
        $oVendor = oxNew('oxvendor');

        return ($this->getActCatType() == 'oxvendor' && $oVendor->load($this->getActCatId())) ? $oVendor : null;
    }

    /**
     * Returns active manufacturer object if available
     *
     * @return oxmanufacturer | null
     */
    public function getActManufacturer()
    {
        $oManufacturer = oxNew('oxmanufacturer');
        $blLoaded = $this->getActCatType() == 'oxmanufacturer' && $oManufacturer->load($this->getActCatId());

        return ($blLoaded) ? $oManufacturer : null;
    }

    /**
     * Returns list type for current seo url
     *
     * @return string
     */
    public function getListType()
    {
        switch ($this->getActCatType()) {
            case 'oxvendor':
                return 'vendor';
            case 'oxmanufacturer':
                return 'manufacturer';
        }
    }

    /**
     * Returns editable object language id
     *
     * @return int
     */
    public function getEditLang()
    {
        return $this->getActCatLang();
    }

    /**
     * Returns alternative seo entry id
     *
     * @return null
     */
    protected function _getAltSeoEntryId()
    {
        return $this->getEditObjectId();
    }

    /**
     * Returns url type
     *
     * @return string
     */
    protected function _getType()
    {
        return 'oxarticle';
    }

    /**
     * Processes parameter before writing to db
     *
     * @param string $sParam parameter to process
     *
     * @return string
     */
    public function processParam($sParam)
    {
        return $this->getActCatId();
    }

    /**
     * Returns current object type seo encoder object
     *
     * @return oxSeoEncoderCategory
     */
    protected function _getEncoder()
    {
        return oxRegistry::get("oxSeoEncoderArticle");
    }

    /**
     * Returns seo uri
     *
     * @return string
     */
    public function getEntryUri()
    {
        $product = oxNew('oxArticle');

        if ($product->load($this->getEditObjectId())) {
            $seoEncoder = $this->_getEncoder();

            switch ($this->getActCatType()) {
                case 'oxvendor':
                    return $seoEncoder->getArticleVendorUri($product, $this->getEditLang());
                case 'oxmanufacturer':
                    return $seoEncoder->getArticleManufacturerUri($product, $this->getEditLang());
                default:
                    if ($this->getActCatId()) {
                        return $seoEncoder->getArticleUri($product, $this->getEditLang());
                    } else {
                        return $seoEncoder->getArticleMainUri($product, $this->getEditLang());
                    }
            }
        }
    }

    /**
     * Returns TRUE, as this view support category selector
     *
     * @return bool
     */
    public function showCatSelect()
    {
        return true;
    }

    /**
     * Returns id of object which must be saved
     *
     * @return string
     */
    protected function _getSaveObjectId()
    {
        return $this->getEditObjectId();
    }

    /**
     * Returns TRUE if current seo entry has fixed state
     *
     * @return bool
     */
    public function isEntryFixed()
    {
        $oDb = oxDb::getDb();

        $sId = $this->_getSaveObjectId();
        $iLang = (int) $this->getEditLang();
        $iShopId = $this->getConfig()->getShopId();
        $sParam = $this->processParam($this->getActCatId());

        $sQ = "select oxfixed from oxseo where
                   oxseo.oxobjectid = " . $oDb->quote($sId) . " and
                   oxseo.oxshopid = '{$iShopId}' and oxseo.oxlang = {$iLang} and oxparams = " . $oDb->quote($sParam);

        return (bool) oxDb::getDb()->getOne($sQ, false, false);
    }
}

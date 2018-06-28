<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

/**
 * WooCommerce Product Core Data Access
 */
trait CoreTrait
{
    
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {

        //====================================================================//
        // Detect Multilangual Mode
        if ($this->multilangMode() != self::$MULTILANG_DISABLED) {
            $VarcharType    = SPL_T_MVARCHAR;
            $TextType       = SPL_T_MTEXT;
        } else {
            $VarcharType    = SPL_T_VARCHAR;
            $TextType       = SPL_T_TEXT;
        }
        
        //====================================================================//
        // Title
        $this->fieldsFactory()->Create($VarcharType)
                ->Identifier("post_title")
                ->Name(__("Title"))
                ->Description(__("Products") . " : " . __("Title"))
                ->MicroData("http://schema.org/Product", "name")
                ->isLogged()
                ->isReadOnly()
                ->isListed()
            ;

        //====================================================================//
        // Title without Options
        $this->fieldsFactory()->Create($VarcharType)
                ->Identifier("base_title")
                ->Name(__("Base Title"))
                ->Group("Meta")
                ->Description(__("Products") . " : " . __("Title without Options"))
                ->MicroData("http://schema.org/Product", "alternateName")
                ->isRequired()
            ;
        
        //====================================================================//
        // Slug
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("post_name")
                ->Name(__("Slug"))
                ->Description(__("Products") . " : " . __("Permalink"))
                ->MicroData("http://schema.org/Product", "urlRewrite")
                ->isNotTested()    // Only Due to LowerCase Convertion
                ->isLogged()
            ;
        
        //====================================================================//
        // Contents
        $this->fieldsFactory()->Create($TextType)
                ->Identifier("post_content")
                ->Name(__("Contents"))
                ->Description(__("Products") . " : " . __("Contents"))
                ->MicroData("http://schema.org/Article", "articleBody")
                ->isLogged()
            ;
        
        //====================================================================//
        // Status
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("post_status")
                ->Name(__("Status"))
                ->Description(__("Products") . " : " . __("Status"))
                ->MicroData("http://schema.org/Article", "status")
                ->AddChoices(get_post_statuses())
                ->isListed()
            ;
        
        //====================================================================//
        // Short Description
        $this->fieldsFactory()->Create($VarcharType)
                ->Identifier("post_excerpt")
                ->Name(__("Product short description"))
                ->Description(__("Products") . " : " . __("Product short description"))
                ->MicroData("http://schema.org/Product", "description");
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return       void
     */
    private function getCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'post_name':
            case 'post_status':
                $this->getSimple($FieldName);
                break;
            
            case 'post_title':
//                //====================================================================//
//                // TODO => With WpMultilang, Titles are not Translated on Variation Posts
//                //====================================================================//
                $this->getMultilangual($FieldName);
                break;

            case 'base_title':
                //====================================================================//
                // Detect Product Variation
                if ($this->isVariantsProduct()) {
                    $this->Object->$FieldName    =  get_post($this->Product->get_parent_id())->post_title;
                } else {
                    $this->Object->$FieldName    =  $this->Object->post_title;
                }
                $this->getMultilangual($FieldName);
                break;
            
            case 'post_content':
            case 'post_excerpt':
                //====================================================================//
                // Detect Product Variation
                if ($this->isVariantsProduct()) {
                    $this->Object->$FieldName    =  get_post($this->Product->get_parent_id())->$FieldName;
                }
                $this->getMultilangual($FieldName);
                break;
            
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
        
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//
      
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return       void
     */
    private function setCoreFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Fullname Writtings
            case 'post_name':
            case 'post_status':
                $this->setSimple($FieldName, $Data);
                break;

            case 'post_title':
                $this->setMultilangual($FieldName, $Data);
                break;
            
            case 'base_title':
                if ($this->isVariantsProduct()) {
                    $this->setSimple(
                        "post_title",
                        $this->decodeMultilang($Data, $this->BaseProduct->get_name()),
                        "BaseObject"
                    );
                    break;
                }
                $this->setMultilangual('post_title', $Data);
                break;
            
            case 'post_content':
            case 'post_excerpt':
                if ($this->isVariantsProduct()) {
                    $this->setMultilangual($FieldName, $Data, "BaseObject");
                    break;
                }
                $this->setMultilangual($FieldName, $Data);
                break;
            default:
                return;
        }
        
        unset($this->In[$FieldName]);
    }
}

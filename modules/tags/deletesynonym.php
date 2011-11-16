<?php
/**
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 */

$http = eZHTTPTool::instance();

$tagID = (int) $Params['TagID'];

if ( $tagID <= 0 )
{
    return $Module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

$tag = eZTagsObject::fetch( $tagID );
if ( !( $tag instanceof eZTagsObject ) )
{
    return $Module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

if ( $tag->MainTagID == 0 )
{
    return $Module->redirectToView( 'delete', array( $tagID ) );
}

if ( $http->hasPostVariable( 'NoButton' ) )
{
    return $Module->redirectToView( 'id', array( $tagID ) );
}

if ( $http->hasPostVariable( 'YesButton' ) )
{
    $db = eZDB::instance();
    $db->begin();

    $parentTag = $tag->getParent();
    if ( $parentTag instanceof eZTagsObject )
    {
        $parentTag->updateModified();
    }

    $mainTagID = $tag->MainTagID;

    $tag->registerSearchObjects();
    if ( $http->hasPostVariable( 'TransferObjectsToMainTag' ) )
    {
        foreach ( $tag->getTagAttributeLinks() as $tagAttributeLink )
        {
            $link = eZTagsAttributeLinkObject::fetchByObjectAttributeAndKeywordID(
                        $tagAttributeLink->ObjectAttributeID,
                        $tagAttributeLink->ObjectAttributeVersion,
                        $tagAttributeLink->ObjectID,
                        $mainTagID );

            if ( !( $link instanceof eZTagsAttributeLinkObject ) )
            {
                $tagAttributeLink->KeywordID = $mainTagID;
                $tagAttributeLink->store();
            }
            else
            {
                $tagAttributeLink->remove();
            }
        }
    }
    else
    {
        foreach ( $tag->getTagAttributeLinks() as $tagAttributeLink )
        {
            $tagAttributeLink->remove();
        }
    }

    $tag->remove();

    $db->commit();

    return $Module->redirectToView( 'id', array( $mainTagID ) );
}

$tpl = eZTemplate::factory();

$tpl->setVariable( 'tag', $tag );

$Result = array();
$Result['content']    = $tpl->fetch( 'design:tags/deletesynonym.tpl' );
$Result['ui_context'] = 'edit';
$Result['path']       = array( array( 'tag_id' => 0,
                                      'text'   => ezpI18n::tr( 'extension/eztags/tags/edit', 'Delete synonym' ),
                                      'url'    => false ) );

$contentInfoArray = array();
$contentInfoArray['persistent_variable'] = false;
if ( $tpl->variable( 'persistent_variable' ) !== false )
    $contentInfoArray['persistent_variable'] = $tpl->variable( 'persistent_variable' );

$Result['content_info'] = $contentInfoArray;

?>

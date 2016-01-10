<?php

class LiamW_410Gone_Listener
{
	/**
	 * Get the data and determine if we need to change the response code to 410.
	 *
	 * @param XenForo_Controller                        $controller
	 * @param XenForo_ControllerResponse_Abstract|false $controllerResponse
	 * @param string                                    $controllerName
	 * @param string                                    $action
	 *
	 * @throws XenForo_Exception
	 */
	public final static function controllerPostDispatch(XenForo_Controller $controller, $controllerResponse, $controllerName, $action)
	{
		if ($controller instanceof XenForo_ControllerPublic_Abstract && $controllerResponse instanceof XenForo_ControllerResponse_Error && $controllerResponse->responseCode == 404)
		{
			$data = array();
			XenForo_CodeEvent::fire('410_gone_data', array(
				&$data,
				$controller,
				$controllerName,
				$action
			));

			$table = $field = '';

			foreach ($data as $_controllerName => $info)
			{
				if ($_controllerName == $controllerName)
				{
					list($table, $field) = $info;
					break;
				}
			}

			if (($id = $controller->getInput()->filterSingle($field, XenForo_Input::UINT)) && $table && $field)
			{
				$db = XenForo_Application::getDb();

				try
				{
					if (!$db->fetchOne("SELECT $field FROM $table WHERE $field = ?",
							$id) && $db->fetchOne("SELECT COUNT(*) FROM $table WHERE $field > ?", $id) > 0
					)
					{
						$controllerResponse->responseCode = 410;
					}
				} catch (Throwable $e)
				{
					XenForo_Error::logException($e, false, "410 Query Failed (Field: $field, Table: $table): ");
				}
			}
		}
	}

	public static function goneThreadData(&$data, XenForo_ControllerPublic_Abstract $controller, $controllerName, $action)
	{
		$data += array(
			'XenForo_ControllerPublic_Thread' => array(
				'xf_thread',
				'thread_id'
			),
			'XenForo_ControllerPublic_Forum' => array(
				'xf_forum',
				'node_id'
			),
			'XenResource_ControllerPublic_Resource' => array(
				'xf_resource',
				'resource_id'
			),
			'XenResource_ControllerPublic_Category' => array(
				'xf_resource_category',
				'resource_category_id'
			),
			'XenForo_ControllerPublic_Member' => array(
				'xf_user',
				'user_id'
			)
		);
	}
}
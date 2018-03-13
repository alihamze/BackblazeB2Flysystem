<?php
	/**
	 * Created by PhpStorm.
	 * User: alihamze
	 * Date: 3/13/18
	 * Time: 7:23 PM
	 */
	
	namespace TechYet\B2Flysystem;
	
	
	class B2FlysystemException extends \Exception {
		const B2_SDK_ERROR = 1000;
		
		const BUCKET_LOAD_ERROR = 1001;
		
		const FILE_UPLOAD_ERROR = 2000;
	}

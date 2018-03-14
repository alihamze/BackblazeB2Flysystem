<?php
	
	namespace TechYet\B2Flysystem;
	
	use League\Flysystem\AdapterInterface;
	use TechYet\B2\Client;
	use TechYet\B2\File;
	
	/**
	 * Created by PhpStorm.
	 * User: alihamze
	 * Date: 3/13/18
	 * Time: 7:17 PM
	 */
	class B2Adapter implements AdapterInterface {
		
		protected $client;
		protected $bucket;
		
		/**
		 * B2Adapter constructor.
		 * @param Client $client
		 * @param $bucketName
		 * @throws B2FlysystemException
		 */
		public function __construct(Client $client, $bucketName) {
			$this->client = $client;
			try {
				$buckets = $this->client->listBuckets();
			} catch (\Exception $e) {
				throw new B2FlysystemException('Error retrieving buckets', B2FlysystemException::B2_SDK_ERROR, $e);
			}
			
			if (!isset($buckets[$bucketName]))
				throw new B2FlysystemException('The request bucket does not exist',
											   B2FlysystemException::BUCKET_LOAD_ERROR);
			
			$this->bucket = $buckets[$bucketName];
		}
		
		/**
		 * Write a new file.
		 *
		 * @param string $path
		 * @param string $contents
		 * @param \League\Flysystem\Config $config Config object
		 *
		 * @return array|false false on failure file meta data on success
		 * @throws B2FlysystemException
		 */
		public function write($path, $contents, \League\Flysystem\Config $config) {
			try {
				$file = $this->bucket->uploadFile($path, $contents);
			} catch (\Exception $e) {
				throw new B2FlysystemException('Error writing file', B2FlysystemException::B2_SDK_ERROR, $e);
			}
			
			return $this->normalizeFileInfo($file);
		}
		
		/**
		 * Write a new file using a stream.
		 *
		 * @param string $path
		 * @param resource $resource
		 * @param \League\Flysystem\Config $config Config object
		 *
		 * @return array|false false on failure file meta data on success
		 * @throws B2FlysystemException
		 */
		public function writeStream($path, $resource, \League\Flysystem\Config $config) {
			return $this->write($path, $resource, $config);
		}
		
		/**
		 * Update a file.
		 *
		 * @param string $path
		 * @param string $contents
		 * @param \League\Flysystem\Config $config Config object
		 *
		 * @return array|false false on failure file meta data on success
		 */
		public function update($path, $contents, \League\Flysystem\Config $config) {
			return false;
		}
		
		/**
		 * Update a file using a stream.
		 *
		 * @param string $path
		 * @param resource $resource
		 * @param \League\Flysystem\Config $config Config object
		 *
		 * @return array|false false on failure file meta data on success
		 * @throws B2FlysystemException
		 */
		public function updateStream($path, $resource, \League\Flysystem\Config $config) {
			return $this->write($path, $resource, $config);
		}
		
		/**
		 * Rename a file.
		 *
		 * @param string $path
		 * @param string $newpath
		 *
		 * @return bool
		 */
		public function rename($path, $newpath) {
			return false;
		}
		
		/**
		 * Copy a file.
		 *
		 * @param string $path
		 * @param string $newpath
		 *
		 * @return bool
		 */
		public function copy($path, $newpath) {
			return false;
		}
		
		/**
		 * Delete a file.
		 *
		 * @param string $path
		 *
		 * @return bool
		 * @throws B2FlysystemException
		 */
		public function delete($path) {
			try {
				return $this->bucket->getFileByName($path)->delete();
			} catch (\Exception $e) {
				throw new B2FlysystemException('Error deleting file', B2FlysystemException::B2_SDK_ERROR, $e);
			}
		}
		
		/**
		 * Delete a directory.
		 *
		 * @param string $dirname
		 *
		 * @return bool
		 * @throws B2FlysystemException
		 */
		public function deleteDir($dirname) {
			return $this->delete($dirname);
		}
		
		/**
		 * Create a directory.
		 *
		 * @param string $dirname directory name
		 * @param \League\Flysystem\Config $config
		 *
		 * @return array|false
		 * @throws B2FlysystemException
		 */
		public function createDir($dirname, \League\Flysystem\Config $config) {
			return $this->write($dirname, '', $config);
		}
		
		/**
		 * Set the visibility for a file.
		 *
		 * @param string $path
		 * @param string $visibility
		 *
		 * @return array|false file meta data
		 */
		public function setVisibility($path, $visibility) {
			return false;
		}
		
		/**
		 * Check whether a file exists.
		 *
		 * @param string $path
		 *
		 * @return array|bool|null
		 * @throws B2FlysystemException
		 */
		public function has($path) {
			try {
				return $this->bucket->fileExists($path);
			} catch (\Exception $e) {
				throw new B2FlysystemException('Error checking for file', B2FlysystemException::B2_SDK_ERROR, $e);
			}
		}
		
		/**
		 * Read a file.
		 *
		 * @param string $path
		 *
		 * @return array|false
		 * @throws B2FlysystemException
		 */
		public function read($path) {
			try {
				return ['contents' => $this->bucket->getFileByName($path)->download()];
			} catch (\Exception $e) {
				throw new B2FlysystemException('Error retrieving file', B2FlysystemException::B2_SDK_ERROR, $e);
			}
		}
		
		/**
		 * Read a file as a stream.
		 *
		 * @param string $path
		 *
		 * @return array|false
		 */
		public function readStream($path) {
			return false;
		}
		
		/**
		 * List contents of a directory.
		 *
		 * @param string $directory
		 * @param bool $recursive
		 *
		 * @return array
		 * @throws B2FlysystemException
		 */
		public function listContents($directory = '', $recursive = false) {
			try {
				$files = $this->bucket->listFileNames(['prefix' => $directory]);
				$returnFiles = [];
				foreach ($files as $file) {
					$returnFiles[] = $this->normalizeFileInfo($file);
				}
				
				return $returnFiles;
			} catch (\Exception $e) {
				throw new B2FlysystemException('Error retrieving files', B2FlysystemException::B2_SDK_ERROR, $e);
			}
		}
		
		/**
		 * Get all the meta data of a file or directory.
		 *
		 * @param string $path
		 *
		 * @return array|false
		 * @throws B2FlysystemException
		 */
		public function getMetadata($path) {
			try {
				return $this->normalizeFileInfo($this->bucket->getFileByName($path));
			} catch (\Exception $e) {
				throw new B2FlysystemException('Error retrieving meta data', B2FlysystemException::B2_SDK_ERROR, $e);
			}
		}
		
		/**
		 * Get the size of a file.
		 *
		 * @param string $path
		 *
		 * @return array|false
		 * @throws B2FlysystemException
		 */
		public function getSize($path) {
			return $this->getMetadata($path);
		}
		
		/**
		 * Get the mimetype of a file.
		 *
		 * @param string $path
		 *
		 * @return array|false
		 * @throws B2FlysystemException
		 */
		public function getMimetype($path) {
			return $this->getMetadata($path);
		}
		
		/**
		 * Get the timestamp of a file.
		 *
		 * @param string $path
		 *
		 * @return array|false
		 * @throws B2FlysystemException
		 */
		public function getTimestamp($path) {
			return $this->getMetadata($path);
		}
		
		/**
		 * Get the visibility of a file.
		 *
		 * @param string $path
		 *
		 * @return array|false
		 */
		public function getVisibility($path) {
			return false;
		}
		
		/**
		 * @param $path
		 * @return string
		 */
		public function getUrl($path) {
			return sprintf('%s/file/%s/%s', $this->bucket->getClient()->getDownloadUrl(), $this->bucket->getName(),
						   $path);
		}
		
		/**
		 * @param $path
		 * @param \DateTimeInterface $expiration
		 * @param array $options
		 * @return bool|string
		 * @throws B2FlysystemException
		 */
		public function getTemporaryUrl($path, $expiration, array $options = []) {
			try {
				$seconds = $expiration->getTimestamp() - time();
				$token = $this->bucket->getFileByName($path)->getDownloadAuthorization([
																						   'validDurationInSeconds' => $seconds,
																					   ]);
				
				return sprintf('%s?Authorization=%s', $this->getUrl($path), $token);
			} catch (\Exception $e) {
				throw new B2FlysystemException('Error retrieving temporary url', B2FlysystemException::B2_SDK_ERROR, $e);
			}
			
		}
		
		/**
		 * @param File $file
		 * @return array
		 */
		protected function normalizeFileInfo(File $file) {
			return [
				'type'      => 'file',
				'mimetype'  => $file->getType(),
				'path'      => $file->getName(),
				'timestamp' => round($file->getUploadedTimestamp() / 1000),
				'size'      => $file->getSize(),
			];
		}
	}

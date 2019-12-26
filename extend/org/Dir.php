<?php
namespace org;

/*
 * 目录操作类
 */
class Dir
{
	/**
	 * 删除目录（包括下面的文件）
	 */
	function delDir($directory)
	{
		if (is_dir($directory) == false)
		{
			exit("The Directory ".$directory." Is Not Exist!");
		}
		$handle = opendir($directory);
		while (($file = readdir($handle)) !== false)
		{
			if ($file != "." && $file != "..")
			{
			is_dir("$directory/$file")?
				Dir::delDir("$directory/$file"):
				unlink("$directory/$file");
			}
		}
		if (readdir($handle) == false)
		{
			closedir($handle);
			try {
                rmdir($directory);
            } catch (\Exception $e) {}
		}
	}
}
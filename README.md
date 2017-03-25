# E621 Download Utility

A hastily put together command line PHP utility to automate mass downloads of files from the E621 image sharing website.

#### Usage

**`php e621.php --tags <tag1,tag2> [options]`**

`--order <order>` Allows you to specify an order, like on the E621 website. For example, `--order random` would order the results randomly. This will not be useful until download limits are introduced, allowing you to, for example, get the top 10 posts of all time.

`--skip <tag1,tag2>` This flag skips any files with the listed tags. There is no limit to the number of tags that can be specified. Tags can alternatively be placed in a file called `skip.txt` in the same directory, with each tag on its own line. An E621 blocklist can be used this way.

`--no-download` As you might expect, this utility will download all files found to a new folder named after your query. This flag will prevent files from being downloaded, and instead just generate an `information.json` file as noted below.

`--no-information` By default, any files found will be downloaded, and their extensive details will be saved to a file in the same directory named `information.json`. Use this flag to prevent the file being created.

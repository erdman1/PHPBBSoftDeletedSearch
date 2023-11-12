# PHPBBSoftDeletedSearch

## Overview

The `erdman\softdeletedsearch` extension for phpBB adds a unique feature allowing moderators to view soft-deleted posts by a specific user. This extension integrates seamlessly with phpBB's user profiles and search functionality, offering a specialized tool for efficient moderation and management of soft-deleted content.

## Features

- Adds a new button on a user's profile, visible only to moderators.
- Allows moderators to view all soft-deleted posts of a specific user.
- Integrates with phpBB's pagination to handle large volumes of posts.
- Includes JavaScript-based dynamic loading of additional search results.

## Requirements

- phpBB 3.3.x or later.
- PHP 5.4 or higher.

## Installation

1. **Download and Extract**: Download the extension and extract it to your phpBB `ext` directory. It should be located under `ext/erdman/softdeletedsearch`.

2. **Enable Extension**: Log in to your phpBB board and go to the Admin Control Panel. Navigate to `Customise` -> `Manage extensions`. Look for `erdman\softdeletedsearch` under the Disabled Extensions list, and click `Enable`.

3. **Verify Installation**: After enabling, verify that the extension is functioning by checking a user's profile for the new button.

## Usage

- As a moderator, navigate to any user's profile.
- Click on the 'Search user’s deleted posts' button to display only their soft-deleted posts.
- Use the pagination or 'Load More' button (if available) to navigate through the posts.

## Customization

- JavaScript parameters such as `maxPagesToLoad` can be adjusted in the provided JavaScript file to suit different forum sizes and user preferences.

## Support

For support, questions, or to report issues, please create an issue on github.

## License

GNU General Public License v2 

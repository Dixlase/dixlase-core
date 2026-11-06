# Media Management

Manage uploaded media files including images, videos, and documents. Upload new files, configure allowed file types and size limits.

## Media Master

Browse all uploaded media files. You can:

- View files in a grid or list layout
- Preview images, videos, and documents
- Search and filter files by type
- Delete files you no longer need

## Media Upload

Upload new files to the media library:

- Drag and drop files or click to browse
- Upload multiple files at once
- Allowed file types and maximum file sizes are enforced based on the media settings

## Media Settings

Configure media handling for your site:

| Setting | Description |
|---------|-------------|
| Allowed File Types | Specify which file extensions can be uploaded (e.g., jpg, png, pdf, mp4) |
| Maximum File Size | Set the maximum upload size per file |

## Importing Files from the Command Line

`dls:media:import` registers local files in the media library without the upload screen — for example images a script or an assistant prepared for an article.

```bash
php artisan dls:media:import cover.png figure-1.jpg --as=editor@example.com
php artisan dls:media:import cover.png --as=3 --json
```

- `--as` (required) names the member the uploads are recorded under, by ID or e-mail address. The member must be active and allowed to upload media; the audit log records each upload under them
- Each file is checked exactly like an upload from the admin: the allowed file types and maximum size from the media settings, the security checks, and SVG sanitising
- A file whose content is identical to an existing media item is not stored again; the existing item is reported as already in the library
- `--json` prints, for each file, its media `id`, `name`, `type`, `width`, `height`, the root-relative `path` the editor inserts, and the absolute `url` — or an `error`
- The command exits non-zero when any file failed; the other files are still imported

## Notes

- Uploaded files are stored in the site's storage directory.
- File type and size restrictions help protect your site from malicious uploads.

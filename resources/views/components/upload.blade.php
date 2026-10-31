@props(['label' => 'Attach a document', 'file' => null])
<label class="upload"><x-icon :name="$file ? 'file-check-2' : 'upload-cloud'" size="24" /><span><strong>{{ $file ?? $label }}</strong><small>{{ $file ? 'Sample document' : 'PDF, Word or image' }}</small></span><input type="file" class="sr-only" data-file-preview><span class="upload-action">{{ $file ? 'Replace' : 'Browse' }}</span></label>

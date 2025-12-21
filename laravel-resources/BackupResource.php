<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'filepath' => $this->filepath,
            'download_url' => $this->when(
                $this->status === 'completed',
                route('backups.download', $this->id)
            ),
            'size' => $this->size,
            'formatted_size' => $this->formatted_size,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('createdBy')),
            'notes' => $this->notes,
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

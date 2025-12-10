import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Loader2 } from 'lucide-react';

interface DeleteConfirmationDialogProps {
  /**
   * Whether the dialog is open
   */
  open: boolean;

  /**
   * Callback when dialog open state changes
   */
  onOpenChange: (open: boolean) => void;

  /**
   * Name of the item being deleted (e.g., "Lokasi Jakarta")
   */
  itemName: string;

  /**
   * Type of entity being deleted (e.g., "lokasi", "karyawan")
   * @default "item"
   */
  entityName?: string;

  /**
   * Optional custom description
   * If not provided, a default message will be shown
   */
  description?: string;

  /**
   * Callback when delete is confirmed
   */
  onConfirm: () => void | Promise<void>;

  /**
   * Whether the delete operation is in progress
   * @default false
   */
  loading?: boolean;

  /**
   * Custom confirm button text
   * @default "Hapus"
   */
  confirmText?: string;

  /**
   * Custom cancel button text
   * @default "Batal"
   */
  cancelText?: string;
}

/**
 * DeleteConfirmationDialog - Reusable delete confirmation dialog
 *
 * Used across 12+ pages for consistent delete confirmation UX.
 * Prevents accidental deletions with a confirmation step.
 *
 * @example
 * ```tsx
 * const [itemToDelete, setItemToDelete] = useState<{ id: string; name: string } | null>(null);
 * const deleteMutation = useDeleteLocation();
 *
 * <DeleteConfirmationDialog
 *   open={!!itemToDelete}
 *   onOpenChange={(open) => !open && setItemToDelete(null)}
 *   itemName={itemToDelete?.name || ''}
 *   entityName="lokasi"
 *   onConfirm={() => deleteMutation.mutate(itemToDelete!.id)}
 *   loading={deleteMutation.isPending}
 * />
 * ```
 */
export function DeleteConfirmationDialog({
  open,
  onOpenChange,
  itemName,
  entityName = 'item',
  description,
  onConfirm,
  loading = false,
  confirmText = 'Hapus',
  cancelText = 'Batal',
}: DeleteConfirmationDialogProps) {
  const defaultDescription = description || (
    <>
      Apakah Anda yakin ingin menghapus {entityName} <strong>{itemName}</strong>?
      <br />
      Tindakan ini tidak dapat dibatalkan.
    </>
  );

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent className="max-w-[90vw] sm:max-w-md rounded-2xl">
        <AlertDialogHeader>
          <AlertDialogTitle>Hapus {entityName}?</AlertDialogTitle>
          <AlertDialogDescription>{defaultDescription}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter className="flex-col gap-2 sm:flex-row">
          <AlertDialogCancel disabled={loading} className="sm:w-auto">
            {cancelText}
          </AlertDialogCancel>
          <AlertDialogAction
            onClick={(e) => {
              e.preventDefault();
              onConfirm();
            }}
            disabled={loading}
            className="bg-destructive text-destructive-foreground hover:bg-destructive/90 sm:w-auto"
          >
            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {loading ? 'Menghapus...' : confirmText}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}

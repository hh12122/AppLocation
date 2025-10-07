import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm as useVeeForm } from 'vee-validate';
import { computed, watch } from 'vue';
import type { z } from 'zod';

/**
 * Composable that combines Inertia's useForm with vee-validate for comprehensive form validation
 *
 * @param initialValues - Initial form values
 * @param validationSchema - Optional Zod schema for client-side validation
 * @returns Combined form utilities with validation
 *
 * @example
 * ```ts
 * const { form, errors, validate, hasErrors } = useFormValidation({
 *   name: '',
 *   email: '',
 * }, nameValidationSchema);
 * ```
 */
export function useFormValidation<T extends Record<string, any>>(
  initialValues: T,
  validationSchema?: z.ZodSchema<any>
) {
  // Inertia form for data management and server-side errors
  const inertiaForm = useInertiaForm(initialValues);

  // Vee-validate form for client-side validation (if schema provided)
  const veeForm = validationSchema
    ? useVeeForm({
        validationSchema: toTypedSchema(validationSchema),
        initialValues,
      })
    : null;

  // Combine errors from both Inertia (server-side) and vee-validate (client-side)
  const combinedErrors = computed(() => {
    const serverErrors = inertiaForm.errors;
    const clientErrors = veeForm?.errors.value || {};

    // Merge both error sources
    const combined: Record<string, string> = { ...serverErrors };

    Object.keys(clientErrors).forEach((key) => {
      if (!combined[key]) {
        combined[key] = clientErrors[key];
      }
    });

    return combined;
  });

  // Check if there are any errors
  const hasErrors = computed(() => Object.keys(combinedErrors.value).length > 0);

  // Get error for a specific field
  const getError = (field: string): string | undefined => {
    return combinedErrors.value[field];
  };

  // Check if a field has an error
  const hasError = (field: string): boolean => {
    return !!combinedErrors.value[field];
  };

  // Validate the form (client-side only)
  const validate = async (): Promise<boolean> => {
    if (!veeForm) return true;
    const result = await veeForm.validate();
    return result.valid;
  };

  // Clear errors for a specific field
  const clearError = (field: string) => {
    if (veeForm) {
      veeForm.setFieldError(field, undefined);
    }
    delete inertiaForm.errors[field];
  };

  // Clear all errors
  const clearErrors = () => {
    if (veeForm) {
      veeForm.resetForm();
    }
    inertiaForm.clearErrors();
  };

  // Sync vee-validate values with Inertia form
  if (veeForm) {
    watch(
      () => veeForm.values,
      (newValues) => {
        Object.keys(newValues).forEach((key) => {
          if (key in inertiaForm) {
            (inertiaForm as any)[key] = newValues[key];
          }
        });
      },
      { deep: true }
    );
  }

  return {
    form: inertiaForm,
    errors: combinedErrors,
    hasErrors,
    getError,
    hasError,
    validate,
    clearError,
    clearErrors,
    // Expose vee-validate form utilities
    defineField: veeForm?.defineField,
    setFieldValue: veeForm?.setFieldValue,
    setFieldError: veeForm?.setFieldError,
  };
}

/**
 * Get input props with error state for UI components
 *
 * @param fieldName - Name of the form field
 * @param error - Error message for the field
 * @returns Object with class name for error styling
 *
 * @example
 * ```ts
 * <Input v-bind="getInputProps('email', errors.email)" />
 * ```
 */
export function getInputProps(fieldName: string, error?: string) {
  return {
    class: error ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : '',
    'aria-invalid': error ? 'true' : 'false',
    'aria-describedby': error ? `${fieldName}-error` : undefined,
  };
}

import { FieldProps, Field as FormikField } from 'formik';
import { forwardRef } from 'react';

import Input from '@/components/elements/Input';

interface OwnProps {
    name: string;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;
}

type Props = OwnProps & Omit<React.InputHTMLAttributes<HTMLInputElement>, 'name'>;

const Field = forwardRef<HTMLInputElement, Props>(
    ({ id, name = false, label, description, validate, ...props }, ref) => (
        <FormikField innerRef={ref} name={name} validate={validate}>
            {({ field, form: { errors, touched } }: FieldProps) => (
                <div className='flex flex-col gap-2'>
                    {label && (
                        <label className='text-sm text-[#a1a1aa]' htmlFor={id}>
                            {label}
                        </label>
                    )}
                    <Input
                        className='px-4 py-2 rounded-lg outline-hidden text-sm'
                        id={id}
                        {...field}
                        {...props}
                    />
                    {touched[field.name] && errors[field.name] ? (
                        <p className={'text-sm font-bold text-[#fca5a5]'}>
                            {(errors[field.name] as string).charAt(0).toUpperCase() +
                                (errors[field.name] as string).slice(1)}
                        </p>
                    ) : description ? (
                        <p className={'text-sm font-bold'}>{description}</p>
                    ) : null}
                </div>
            )}
        </FormikField>
    ),
);
Field.displayName = 'Field';

export default Field;

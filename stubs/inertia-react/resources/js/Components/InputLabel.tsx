import { LabelHTMLAttributes } from 'react'; // ts-only
// ts-only
export default function InputLabel({ value, className = '', children, ...props }/* ts-begin */: LabelHTMLAttributes<HTMLLabelElement> & { value?: string }/* ts-end */) {
    return (
        <label {...props} className={`block font-medium text-sm text-gray-700 dark:text-gray-300 ` + className}>
            {value ? value : children}
        </label>
    );
}

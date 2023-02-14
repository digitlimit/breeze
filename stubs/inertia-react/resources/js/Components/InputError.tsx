import { HTMLAttributes } from 'react'; // ts-only
// ts-only
export default function InputError({ message, className = '', ...props }/* ts-begin */: HTMLAttributes<HTMLParagraphElement> & { message?: string }/* ts-end */) {
    return message ? (
        <p {...props} className={'text-sm text-red-600 dark:text-red-400 ' + className}>
            {message}
        </p>
    ) : null;
}

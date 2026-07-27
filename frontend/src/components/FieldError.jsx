export default function FieldError({ errors, name }) {
  const message = errors?.[name]?.[0]

  if (!message) {
    return null
  }

  return <p className="error-text">{message}</p>
}

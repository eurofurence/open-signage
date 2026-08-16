export default function truncate(value, length, useWordBoundary) {
  if (value.length <= length) {
    return value;
  }

  const subString = value.slice(0, length - 1);

  return `${useWordBoundary ? subString.slice(0, subString.lastIndexOf(' ')) : subString} …`;
}

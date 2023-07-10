# Testing

To test the behaviour of this program, we can parse the Deployer output since we cannot include or use (or can we) the recipe directly in the test code. However, we can run the `dep` command directly and observe the output, so that's that.

## Output parsing algorithm

1. Iterate output lines

2. Put every single-liner to the logs collection

3. If we see exception, this line and the next lines until it sees non-exception related log will be put to one log entry

4. If we see "Task .+? failed!" line but haven't seen exception
